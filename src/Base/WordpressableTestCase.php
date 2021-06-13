<?php

namespace Prokl\WordpressCi\Base;

use Exception;
use Prokl\TestingTools\Base\BaseTestCase;
use Prokl\WordpressCi\Bootstrap;
use Prokl\WordpressCi\ClassUtils;
use Prokl\WordpressCi\Database;
use Prokl\WordpressCi\Exceptions\WPDieException;
use Prokl\WordpressCi\Migrations\ArrilotMigratorProcessor;
use Prokl\WordpressCi\Migrator;
use Prokl\WordpressCi\Traits\CustomDumpTrait;
use Prokl\WordpressCi\Traits\ResetDatabaseTrait;
use Prokl\WordpressCi\Traits\UseMigrationsTrait;
use Prokl\WordpressCi\WpInitializer;
use WP_Error;

/**
 * Class WordpressableTestCase
 * @package Prokl\BitrixTestingTools\Base
 *
 * @since 13.06.2021 Расширение функционала.
 */
class WordpressableTestCase extends BaseTestCase
{
    /**
     * @var array $hooks_saved Бэкап хуков.
     */
    protected static $hooks_saved;

    /**
     * @var boolean $dropBase Сбрасывать ли базу после каждого теста.
     */
    private $dropBase = false;

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function setUp(): void
    {
        set_time_limit(0);

        parent::setUp();

        if (!self::$hooks_saved) {
            $this->backupHooks();
        }

        $this->setupDatabaseData();
        $this->dropBase = $this->needDropBase();

        $dbManager = $this->getDbManager();

        if ($this->dropBase) {
            $dbManager->dropBase();
            $dbManager->createDatabaseIfNotExist();
            $this->migrateDatabase();
        } else {
            $dbManager->createDatabaseIfNotExist();

            if ($dbManager->hasEmptyBase()) {
                $this->migrateDatabase();
            }
        }

        Bootstrap::bootstrap();

        $initializer = new WpInitializer();

        // Если используется трэйт ActivatePluginsTrait, то установить и активировать плагины
        if (method_exists($this, 'activatePlugins')) {
            $this->activatePlugins();
        }


        // Миграции
        if ($this->useMigrations()) {
            $migrator = new ArrilotMigratorProcessor();
            /** @noinspection PhpUndefinedMethodInspection */
            $migrator->setMigrationsDir($this->getMigrationsDir())
                      ->init();

            $migrator->createMigrationsTable();
            $migrator->migrate();
        }

        // Подмена фэйкера экземпляром с провайдерами Wordpress и Picsum.
        $this->faker= $initializer->getGenerator();

        add_filter('wp_die_handler', [$this, 'wpDieHandler']);
    }

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        global $wpdb;

        $wpdb->suppress_errors = false;
        $wpdb->show_errors     = true;

        $class = get_called_class();

        if (method_exists($class, 'wpSetUpBeforeClass')) {
            call_user_func([$class, 'wpSetUpBeforeClass'], []);
        }
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->dropBase) {
            $dbManager = $this->getDbManager();
            $dbManager->dropBase();
        }

        // Reset globals related to the post loop and `setup_postdata()`.
        $post_globals = [
            'post',
            'id',
            'authordata',
            'currentday',
            'currentmonth',
            'page',
            'pages',
            'multipage',
            'more',
            'numpages',
        ];

        foreach ($post_globals as $global) {
            $GLOBALS[$global] = null;
        }

        // Reset $wp_sitemap global so that sitemap-related dynamic $wp->public_query_vars are added when the next test runs.
        $GLOBALS['wp_sitemaps'] = null;

        $this->unregisterAllMetaKeys();
        remove_filter('wp_die_handler', [$this, 'wpDieHandler']);

        $this->restoreHooks();
        self::flushCache();

        wp_set_current_user(0);
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        $class = get_called_class();

        if (method_exists($class, 'wpTearDownAfterClass')) {
            call_user_func([$class, 'wpTearDownAfterClass'], []);
        }
    }

    /**
     * Путь, где находится Wordpress.
     *
     * @return string
     */
    protected static function getWordpressBaseDir() : string
    {
        return __DIR__. '/../../files';
    }

    /**
     * Параметры подключения к тестовой базе.
     *
     * @return void
     */
    protected function setupDatabaseData() : void
    {
        putenv('MYSQL_HOST=localhost');
        putenv('MYSQL_DATABASE=wordpress_ci');
        putenv('MYSQL_USER=root');
        putenv('MYSQL_PASSWORD=');
    }


    /**
     * Saves the action and filter-related globals so they can be restored later.
     *
     * Stores $wp_actions, $wp_current_filter, and $wp_filter on a class variable
     * so they can be restored on tearDown() using _restore_hooks().
     *
     * @global array $wp_actions
     * @global array $wp_current_filter
     * @global array $wp_filter
     *
     * @return void
     */
    protected function backupHooks() : void
    {
        $globals = ['wp_actions', 'wp_current_filter'];
        foreach ($globals as $key) {
            self::$hooks_saved[$key] = $GLOBALS[$key];
        }

        self::$hooks_saved['wp_filter'] = [];
        foreach ($GLOBALS['wp_filter'] as $hook_name => $hook_object) {
            self::$hooks_saved['wp_filter'][$hook_name] = clone $hook_object;
        }
    }

    /**
     * Restores the hook-related globals to their state at setUp()
     * so that future tests aren't affected by hooks set during this last test.
     *
     * @global array $wp_actions
     * @global array $wp_current_filter
     * @global array $wp_filter
     *
     * @return void
     */
    protected function restoreHooks() : void
    {
        $globals = ['wp_actions', 'wp_current_filter'];
        foreach ($globals as $key) {
            if (isset(self::$hooks_saved[$key])) {
                $GLOBALS[ $key ] = self::$hooks_saved[$key];
            }
        }

        if (isset(self::$hooks_saved['wp_filter'])) {
            $GLOBALS['wp_filter'] = [];
            foreach (self::$hooks_saved['wp_filter'] as $hook_name => $hook_object) {
                $GLOBALS['wp_filter'][$hook_name] = clone $hook_object;
            }
        }
    }

    /**
     * Загрузить дамп - кастомный или нативный.
     *
     * @return void
     */
    private function migrateDatabase() : void
    {
        $this->useCustomDump() ? Migrator::migrate($this->getDumpPath()) : Bootstrap::migrate();
    }

    /**
     * Экземпляр менеджера БД.
     *
     * @return Database
     */
    private function getDbManager() : Database
    {
        return new Database(
            getenv('MYSQL_HOST', true) ?: getenv('MYSQL_HOST'),
            getenv('MYSQL_DATABASE', true) ?: getenv('MYSQL_DATABASE'),
            getenv('MYSQL_USER', true) ?: getenv('MYSQL_USER'),
            getenv('MYSQL_PASSWORD', true) ?: getenv('MYSQL_PASSWORD')
        );
    }

    /**
     * Нужно ли сбрасывать базу. Признак - трэйт ResetDatabaseTrait.
     *
     * @return boolean
     */
    private function needDropBase() : bool
    {
        return $this->hasTrait(ResetDatabaseTrait::class);
    }

    /**
     * Использовать ли кастомный дамп. Признак - трэйт CustomDumpTrait.
     *
     * @return boolean
     */
    private function useCustomDump() : bool
    {
        return $this->hasTrait(CustomDumpTrait::class);
    }

    /**
     * Использовать ли миграции. Признак - трэйт UseMigrationsTrait.
     *
     * @return boolean
     */
    private function useMigrations() : bool
    {
        return $this->hasTrait(UseMigrationsTrait::class);
    }

    /**
     * Имеет ли экземпляр класса тот или иной трэйт.
     *
     * @param string $trait Trait.
     *
     * @return boolean
     */
    private function hasTrait(string $trait) : bool
    {
        $traits = ClassUtils::class_uses_recursive($this);

        return in_array($trait, $traits, true);
    }

    /**
     * Обработчик wp_die.
     *
     * @param string | WP_Error $message The `wp_die()` message.
     *
     * @return void
     *
     * @throws WPDieException Exception containing the message.
     */
    protected function wpDieHandler($message) : void
    {
        if (is_wp_error($message)) {
            $message = $message->get_error_message();
        }

        if (!is_scalar($message)) {
            $message = '0';
        }

        throw new WPDieException($message);
    }

    /**
     * Flushes the WordPress object cache.
     *
     * @return void
     */
    public static function flushCache() : void
    {
        global $wp_object_cache;

        $wp_object_cache->group_ops = [];
        $wp_object_cache->stats = [];
        $wp_object_cache->memcache_debug = [];
        $wp_object_cache->cache = [];

        if (method_exists($wp_object_cache, '__remoteset')) {
            $wp_object_cache->__remoteset();
        }

        wp_cache_flush();
        wp_cache_add_global_groups([
            'users',
            'userlogins',
            'usermeta',
            'user_meta',
            'useremail',
            'userslugs',
            'site-transient',
            'site-options',
            'blog-lookup',
            'blog-details',
            'rss',
            'global-posts',
            'blog-id-cache',
            'networks',
            'sites',
            'site-details',
            'blog_meta',
        ]);

        wp_cache_add_non_persistent_groups(['comment', 'counts', 'plugins']);
    }

    /**
     * Clean up any registered meta keys.
     *
     * @since 5.1.0
     *
     * @global array $wp_meta_keys
     */
    private function unregisterAllMetaKeys()
    {
        global $wp_meta_keys;
        if (!is_array($wp_meta_keys)) {
            return;
        }

        foreach ($wp_meta_keys as $object_type => $type_keys) {
            foreach ($type_keys as $object_subtype => $subtype_keys) {
                foreach ($subtype_keys as $key => $value) {
                    unregister_meta_key($object_type, $key, $object_subtype);
                }
            }
        }
    }
}

<?php

namespace Prokl\WordpressCi\Migrations;

use Arrilot\BitrixMigrations\Migrator;
use Arrilot\BitrixMigrations\Storages\WordpressDatabaseStorage;
use Arrilot\BitrixMigrations\TemplatesCollection;
use Exception;

/**
 * Class ArrilotMigratorProcessor
 * @package Prokl\WordpressCi\Migrations
 *
 * @since 24.04.2021
 */
class ArrilotMigratorProcessor
{
    /**
     * @var string[] $config Конфигурация.
     */
    private $config = [
        'table' => 'arrilot_migrations',
        'dir' => './migrations',
    ];

    /**
     * @var WordpressDatabaseStorage $database БД.
     */
    private $database;

    /**
     * @var TemplatesCollection $templates Шаблоны миграций.
     */
    private $templates;

    /**
     * @var Migrator $migrator
     */
    private $migrator;

    /**
     * ArrilotMigratorProcessor constructor.
     */
    public function __construct()
    {
        $this->database = new WordpressDatabaseStorage($this->config['table']);
        $this->templates = new TemplatesCollection();
        $this->templates->registerBasicTemplates();
    }

    /**
     * Инициализация.
     *
     * @return $this
     */
    public function init() : self
    {
        $this->migrator = new Migrator($this->config, $this->templates, $this->database);
        
        return $this;
    }

    /**
     * Задать директорию с миграциями.
     *
     * @param string $dir Директория с миграциями.
     *
     * @return $this
     */
    public function setMigrationsDir(string $dir) : self
    {
      $this->config['dir'] = $dir;
      
      return $this;
    }

    /**
     * Создать таблицу миграций.
     *
     * @return boolean
     */
    public function createMigrationsTable() : bool
    {
        if ($this->database->checkMigrationTableExistence()) {
            return false;
        }

        $this->database->createMigrationTable();

        return true;
    }

    /**
     * Запустить миграции.
     *
     * @return void
     * @throws Exception
     */
    public function migrate() : void
    {
        $toRun = $this->migrator->getMigrationsToRun();

        if (!empty($toRun)) {
            foreach ($toRun as $migration) {
                $this->migrator->runMigration($migration);
            }
        }
    }

    /**
     * Создать миграцию по шаблону.
     *
     * @param string $name     Название миграции.
     * @param string $template Шаблон.
     *
     * @return void
     */
    public function makeMigration(string $name, string $template = 'default') : void
    {
        $this->migrator->createMigration($name, $template);
    }
}
<?php

namespace Prokl\WordpressCi\Traits;

use WP;
use WP_Error;
use WP_Query;
use WP_Rewrite;
use wpdb;

/**
 * Trait WordpressTrait
 * @package Prokl\WordpressCi\Traits
 *
 * @since 15.06.2021
 */
trait WordpressTrait
{

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
     * Asserts that the given value is an instance of WP_Error.
     *
     * @param mixed  $actual  The value to check.
     * @param string $message Optional. Message to display when the assertion fails.
     *
     * @return void
     */
    protected function assertWPError($actual, string $message = '') : void
    {
        $this->assertInstanceOf('WP_Error', $actual, $message);
    }

    /**
     * Asserts that the given value is not an instance of WP_Error.
     *
     * @param mixed  $actual  The value to check.
     * @param string $message Optional. Message to display when the assertion fails.
     *
     * @return void
     */
    protected function assertNotWPError($actual, string $message = '') : void
    {
        if ('' === $message && is_wp_error($actual)) {
            $message = $actual->get_error_message();
        }

        $this->assertNotInstanceOf('WP_Error', $actual, $message);
    }


    /**
     * Sets the global state to as if a given URL has been requested.
     *
     * This sets:
     * - The super globals.
     * - The globals.
     * - The query variables.
     * - The main query.
     *
     * @param string $url The URL for the request.
     *
     * @return void
     */
    protected function goTo(string $url)
    {
        /*
         * Note: the WP and WP_Query classes like to silently fetch parameters
         * from all over the place (globals, GET, etc), which makes it tricky
         * to run them more than once without very carefully clearing everything.
         */
        $_GET  = [];
        $_POST = [];
        foreach ([
                     'query_string',
                     'id',
                     'postdata',
                     'authordata',
                     'day',
                     'currentmonth',
                     'page',
                     'pages',
                     'multipage',
                     'more',
                     'numpages',
                     'pagenow',
                     'current_screen',
                 ] as $v) {
            if (isset($GLOBALS[$v])) {
                unset($GLOBALS[$v]);
            }
        }

        $parts = parse_url($url);
        if (isset($parts['scheme'])) {
            $req = isset($parts['path']) ? $parts['path'] : '';
            if (isset($parts['query'])) {
                $req .= '?' . $parts['query'];
                // Parse the URL query vars into $_GET.
                parse_str($parts['query'], $_GET);
            }
        } else {
            $req = $url;
        }
        if (!isset($parts['query'])) {
            $parts['query'] = '';
        }

        $_SERVER['REQUEST_URI'] = $req;
        unset($_SERVER['PATH_INFO']);

        self::flushCache();
        unset($GLOBALS['wp_query'], $GLOBALS['wp_the_query']);
        $GLOBALS['wp_the_query'] = new WP_Query();
        $GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];

        $public_query_vars  = $GLOBALS['wp']->public_query_vars;
        $private_query_vars = $GLOBALS['wp']->private_query_vars;

        $GLOBALS['wp']                     = new WP();
        $GLOBALS['wp']->public_query_vars  = $public_query_vars;
        $GLOBALS['wp']->private_query_vars = $private_query_vars;

        $this->cleanupQueryVars();

        $GLOBALS['wp']->main($parts['query']);
    }

    /**
     * @return void
     */
    protected function cleanupQueryVars(): void
    {
        // Clean out globals to stop them polluting wp and wp_query.
        foreach ($GLOBALS['wp']->public_query_vars as $v) {
            unset($GLOBALS[$v]);
        }

        foreach ($GLOBALS['wp']->private_query_vars as $v) {
            unset($GLOBALS[$v]);
        }

        foreach (get_taxonomies([], 'objects') as $t) {
            if ($t->publicly_queryable && !empty($t->query_var)) {
                $GLOBALS['wp']->add_query_var($t->query_var);
            }
        }

        foreach (get_post_types([], 'objects') as $t) {
            if (is_post_type_viewable($t) && !empty($t->query_var)) {
                $GLOBALS['wp']->add_query_var($t->query_var);
            }
        }
    }


    /**
     * Checks each of the WP_Query is_* functions/properties against expected boolean value.
     *
     * Any properties that are listed by name as parameters will be expected to be true; all others are
     * expected to be false. For example, assertQueryTrue( 'is_single', 'is_feed' ) means is_single()
     * and is_feed() must be true and everything else must be false to pass.
     *
     * @param string ...$prop Any number of WP_Query properties that are expected to be true for the current request.
     */
    protected function assertQueryTrue(...$prop)
    {
        global $wp_query;

        $all = [
            'is_404',
            'is_admin',
            'is_archive',
            'is_attachment',
            'is_author',
            'is_category',
            'is_comment_feed',
            'is_date',
            'is_day',
            'is_embed',
            'is_feed',
            'is_front_page',
            'is_home',
            'is_privacy_policy',
            'is_month',
            'is_page',
            'is_paged',
            'is_post_type_archive',
            'is_posts_page',
            'is_preview',
            'is_robots',
            'is_favicon',
            'is_search',
            'is_single',
            'is_singular',
            'is_tag',
            'is_tax',
            'is_time',
            'is_trackback',
            'is_year',
        ];

        foreach ($prop as $true_thing) {
            $this->assertContains($true_thing, $all, "Unknown conditional: {$true_thing}.");
        }

        $passed = true;
        $message = '';

        foreach ($all as $query_thing) {
            $result = is_callable($query_thing) ? call_user_func($query_thing) : $wp_query->$query_thing;

            if (in_array($query_thing, $prop, true)) {
                if (!$result) {
                    $message .= $query_thing.' is false but is expected to be true. '.PHP_EOL;
                    $passed = false;
                }
            } elseif ($result) {
                $message .= $query_thing.' is true but is expected to be false. '.PHP_EOL;
                $passed = false;
            }
        }

        if (!$passed) {
            $this->fail($message);
        }
    }

    /**
     * Returns a list of all files contained inside the `uploads` directory.
     *
     * @return array List of file paths.
     */
    protected function scanUserUploads() : array
    {
        static $files = [];

        if (!empty($files)) {
            return $files;
        }

        $uploads = wp_upload_dir();
        $files = $this->filesInDir($uploads['basedir']);

        return $files;
    }

    /**
     * Resets permalinks and flushes rewrites.
     *
     * @global WP_Rewrite $wp_rewrite
     *
     * @param string $structure Optional. Permalink structure to set. Default empty.
     *
     * @return void
     */
    protected function setPermalinkStructure(string $structure = '')
    {
        global $wp_rewrite;

        $wp_rewrite->init();
        $wp_rewrite->set_permalink_structure($structure);
        $wp_rewrite->flush_rules();
    }

    /**
     * Creates an attachment post from an uploaded file.
     *
     * @param array   $upload         Array of information about the uploaded file, provided by wp_upload_bits().
     * @param integer $parent_post_id Optional. Parent post ID.
     *
     * @return integer|WP_Error The attachment ID on success. The value 0 or WP_Error on failure.
     */
    protected function makeAttachment(array $upload, int $parent_post_id = 0)
    {
        $type = '';
        if (!empty($upload['type'])) {
            $type = $upload['type'];
        } else {
            $mime = wp_check_filetype($upload['file']);
            if ($mime) {
                $type = $mime['type'];
            }
        }

        $attachment = [
            'post_title' => wp_basename($upload['file']),
            'post_content' => '',
            'post_type' => 'attachment',
            'post_parent' => $parent_post_id,
            'post_mime_type' => $type,
            'guid' => $upload['url'],
        ];

        $id = wp_insert_attachment($attachment, $upload['file'], $parent_post_id);
        wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));

        return $id;
    }

    /**
     * Updates the modified and modified GMT date of a post in the database.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param integer $post_id Post ID.
     * @param string  $date    Post date, in the format YYYY-MM-DD HH:MM:SS.
     *
     * @return integer|false   1 on success, or false on error.
     */
    protected function updatePostModified(int $post_id, string $date)
    {
        global $wpdb;

        return $wpdb->update(
            $wpdb->posts,
            [
                'post_modified'     => $date,
                'post_modified_gmt' => $date,
            ],
            [
                'ID' => $post_id,
            ],
            [
                '%s',
                '%s',
            ],
            [
                '%d',
            ]
        );
    }

    /**
     * Touches the given file and its directory if it doesn't already exist.
     *
     * This can be used to ensure a file that is implictly relied on in a test exists
     * without it having to be built.
     *
     * @param string $file The file name.
     *
     * @return void
     */
    public static function touch(string $file) : void
    {
        if (file_exists($file)) {
            return;
        }

        $dir = dirname($file);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        touch($file);
    }

    /**
     * Unregister existing post types and register defaults.
     *
     * Run before each test in order to clean up the global scope, in case
     * a test forgets to unregister a post type on its own, or fails before
     * it has a chance to do so.
     *
     * @return void
     */
    protected function resetPostTypes() : void
    {
        foreach (get_post_types([], 'objects') as $pt) {
            if (empty($pt->tests_no_auto_unregister)) {
                $this->unregisterPostType($pt->name);
            }
        }

        create_initial_post_types();
    }

    /**
     * Unregister existing taxonomies and register defaults.
     *
     * Run before each test in order to clean up the global scope, in case
     * a test forgets to unregister a taxonomy on its own, or fails before
     * it has a chance to do so.
     *
     * @return void
     */
    protected function resetTaxonomies() : void
    {
        foreach (get_taxonomies() as $tax) {
            $this->unregisterTaxonomy($tax);
        }

        create_initial_taxonomies();
    }

    /**
     * Unregister non-built-in post statuses.
     *
     * @return void
     */
    protected function resetPostStatuses() : void
    {
        foreach (get_post_stati(['_builtin' => false]) as $post_status) {
            $this->unregisterPostStatus($post_status);
        }
    }

    /**
     * Removes the post type and its taxonomy associations.
     *
     * @param string $cpt_name Тип поста.
     *
     * @return void
     */
    private function unregisterPostType(string $cpt_name) : void
    {
        unregister_post_type($cpt_name);
    }

    /**
     * @param string $taxonomy_name Таксономия.
     *
     * @return void
     */
    private function unregisterTaxonomy(string $taxonomy_name) : void
    {
        unregister_taxonomy($taxonomy_name);
    }

    /**
     * Unregister a post status.
     *
     * @param string $status Status.
     *
     * @return void
     */
    private function unregisterPostStatus(string $status) : void
    {
        unset($GLOBALS['wp_post_statuses'][ $status ]);
    }

    /**
     * Disables the WP die handler.
     *
     * @return void
     */
    protected function disableWpDie() : void
    {
        $GLOBALS['_wp_die_disabled'] = true;
    }

    /**
     * Enables the WP die handler.
     *
     * @return void
     */
    protected function enableWpDie() : void
    {
        $GLOBALS['_wp_die_disabled'] = false;
    }

    /**
     * Dies without an exit.
     *
     * @param string $message The message.
     * @param string $title   The title.
     * @param array  $args    Array with arguments.
     *
     * @return void
     */
    protected function wpDieHandlerTxt(string $message, string $title, array $args) : void
    {
        echo "\nwp_die called\n";
        echo "Message: $message\n";

        if (! empty($title)) {
            echo "Title: $title\n";
        }

        if (! empty($args)) {
            echo "Args: \n";
            foreach ($args as $k => $v) {
                echo "\t $k : $v\n";
            }
        }
    }

}

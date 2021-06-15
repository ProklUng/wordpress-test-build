<?php

namespace Prokl\WordpressCi;

use InvalidArgumentException;

/**
 * Class Bootstrap
 * @package Prokl\WordpressCi
 */
class Bootstrap
{
    /**
     * @return void
     */
    public static function migrate() : void
    {
        $db = mysqli_connect(
            getenv('MYSQL_HOST', true) ?: getenv('MYSQL_HOST'),
            getenv('MYSQL_USER', true) ?: getenv('MYSQL_USER'),
            getenv('MYSQL_PASSWORD', true) ?: getenv('MYSQL_PASSWORD'),
            getenv('MYSQL_DATABASE', true) ?: getenv('MYSQL_DATABASE')
        );

        if (!$db) {
            throw new InvalidArgumentException('Mysql connection error.');
        }

        $sqlDump = new SqlDump(__DIR__ . '/../dump.sql');
        foreach ($sqlDump->parse() as $query) {
            mysqli_query($db, $query);
        }

        mysqli_close($db);
    }

    /**
     * @return void
     */
    public static function bootstrap() : void
    {
        $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../files/');

        $GLOBALS['_wp_die_disabled'] = true;

        // Prevent updating translations asynchronously.
        self::testsAddFilter('async_update_translation', '__return_false');
        // Disable background updates.
        self::testsAddFilter('automatic_updater_disabled', '__return_true');

        // Preset WordPress options defined in bootstrap file.
        // Used to activate themes, plugins, as well as other settings.
        if (isset($GLOBALS['wp_tests_options'])) {
            function wp_tests_options($value)
            {
                $key = substr(current_filter(), strlen('pre_option_'));

                return $GLOBALS['wp_tests_options'][$key];
            }

            foreach (array_keys($GLOBALS['wp_tests_options']) as $key) {
                self::testsAddFilter('pre_option_'.$key, 'wp_tests_options');
            }
        }

        require_once __DIR__ . '/../files/wp-blog-header.php';
    }

    /**
     * Adds hooks before loading WP.
     *
     * @see add_filter()
     *
     * @param string   $tag             The name of the filter to hook the $function_to_add callback to.
     * @param callable $function_to_add The callback to be run when the filter is applied.
     * @param integer  $priority        Optional. Used to specify the order in which the functions
     *                                  associated with a particular action are executed.
     *                                  Lower numbers correspond with earlier execution,
     *                                  and functions with the same priority are executed.
     *                                  in the order in which they were added to the action. Default 10.
     * @param integer  $accepted_args   Optional. The number of arguments the function accepts. Default 1.
     * @return true
     */
    public static function testsAddFilter(string $tag, $function_to_add, int $priority = 10, int $accepted_args = 1) : bool
    {
        global $wp_filter;

        if (function_exists('add_filter')) {
            add_filter($tag, $function_to_add, $priority, $accepted_args);
        } else {
            $idx = self::testFilterBuildUniqueId($tag, $function_to_add, $priority);

            $wp_filter[$tag][$priority][$idx] = [
                'function' => $function_to_add,
                'accepted_args' => $accepted_args,
            ];
        }

        return true;
    }

    /**
     * Generates a unique function ID based on the given arguments.
     *
     * @see _wp_filter_build_unique_id()
     *
     * @param string   $tag      Unused. The name of the filter to build ID for.
     * @param callable $function The function to generate ID for.
     * @param integer  $priority Unused. The order in which the functions
     *                           associated with a particular action are executed.
     * @return string Unique function ID for usage as array key.
     */
    private static function testFilterBuildUniqueId(string $tag, $function, $priority)
    {
        if (is_string($function)) {
            return $function;
        }

        if (is_object($function)) {
            // Closures are currently implemented as objects.
            $function = array($function, '');
        } else {
            $function = (array)$function;
        }

        if (is_object($function[0])) {
            // Object class calling.
            return spl_object_hash($function[0]).$function[1];
        } elseif (is_string($function[0])) {
            // Static calling.
            return $function[0].'::'.$function[1];
        }
    }
}

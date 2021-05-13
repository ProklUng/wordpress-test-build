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
        require_once __DIR__ . '/../files/wp-blog-header.php';
    }
}
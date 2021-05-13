<?php

namespace Prokl\WordpressCi\Tests;

use Prokl\WordpressCi\Bootstrap;
use Prokl\WordpressCi\Tests\Base\WordpressableTestCase;

/**
 * Class BootstrapTest
 * @package Prokl\WordpressCi\Tests
 *
 * @since 13.05.2021
 */
class BootstrapTest extends WordpressableTestCase
{
    /**
     * Создание базы и загрузка Wordpress.
     *
     * @return void
     */
    public function testBootstrap() : void
    {
        global $wp;

        $this->assertNotNull($wp);
    }
}
<?php

namespace Prokl\WordpressCi\Tests\Traits;

/**
 * Trait CustomDumpTrait
 * Использовать свой дамп базы для тестов. Применяется в сочетании с трэйтом ResetDatabaseTrait.
 * @package Prokl\WordpressCi\Tests\Traits
 *
 * @since 24.04.2021
 */
trait CustomDumpTrait
{
    /**
     * Путь к кастомному дампу БД.
     *
     * @return string
     */
    abstract protected function getDumpPath() : string;
}
<?php

namespace Prokl\WordpressCi\Traits;

/**
 * Trait CustomDumpTrait
 * Использовать свой дамп базы для тестов. Применяется в сочетании с трэйтом ResetDatabaseTrait.
 * @package Prokl\WordpressCi\Traits
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
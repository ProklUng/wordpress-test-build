<?php

namespace Prokl\WordpressCi\Traits;

/**
 * Trait UseMigrationsTrait
 * Использовать миграции.
 * @package Prokl\WordpressCi\Traits
 *
 * @since 24.04.2021
 */
trait UseMigrationsTrait
{
    /**
     * Путь к директории с миграциями.
     *
     * @return string
     */
    abstract protected function getMigrationsDir() : string;

    /**
     * Создать миграцию.
     *
     * @param string $name     Название.
     * @param string $template Шаблон.
     *
     * @return void
     */
    protected function makeMigration(string $name, string $template) : void
    {
        $migrator = new ArrilotMigratorProcessor();
        $migrator->setMigrationsDir($this->getMigrationsDir())
                 ->init();

        $migrator->makeMigration($name, $template);
    }
}
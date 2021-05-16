<?php

namespace Prokl\WordpressCi\Traits;

use Prokl\WordpressCi\PluginActivator;

/**
 * Trait ActivatePluginsTrait
 * @package Prokl\WordpressCi\Traits
 *
 * @since 16.05.2021
 */
trait ActivatePluginsTrait
{
    /**
     * @var string $pluginSrcDir
     */
    protected static $pluginSrcDir = __DIR__ . '/files';

    /**
     * @var array $plugins Плагины к активации.
     * Формат: ['директория с плагином' => путь к основному файлу плагина]
     */
    protected static $plugins = [];

    /**
     * Активация плагинов по списку.
     *
     * @return void
     */
    protected function activatePlugins() : void
    {
        $activator = new PluginActivator();

        foreach (static::$plugins as $plugin => $pluginMainFile) {
            $activator->installPlugin(
                $plugin,
                self::$pluginSrcDir,
                self::getWordpressBaseDir() . '/wp-content/plugins'
            );

            $activator->activatePlugin($pluginMainFile);
        }
    }
}
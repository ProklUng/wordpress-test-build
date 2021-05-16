<?php

namespace Prokl\WordpressCi;

use Exception;
use FilesystemIterator;
use RuntimeException;

require_once(ABSPATH.'wp-includes/pluggable.php');
require_once(ABSPATH.'wp-admin/includes/plugin-install.php');
require_once(ABSPATH.'wp-admin/includes/file.php');
require_once(ABSPATH.'wp-admin/includes/misc.php');
require_once(ABSPATH.'wp-admin/includes/plugin.php');
require_once(ABSPATH.'wp-admin/includes/class-wp-upgrader.php');

/**
 * Class PluginActivator
 * @package Prokl\WordpressCi
 *
 * @since 15.05.2021
 */
class PluginActivator
{
    /**
     * @var array $requiredPlugins
     */
    private $requiredPlugins = [];

    /**
     * @param string $plugin Путь к основному файлу плагина. Формат - папка/файл.
     *
     * @return boolean|string
     */
    public function activatePlugin(string $plugin)
    {
        $plugin_mainfile = trailingslashit(WP_PLUGIN_DIR) . $plugin;

        /* Nothing to do, when plugin already active.
         *
         * WARNING: When a plugin has been removed by ftp,
         *          WordPress will still consider it active,
         *          untill the plugin list has been visited
         *          (and it checks the existence of it).
         */
        if (\is_plugin_active($plugin)) {
            // Make sure the plugin is still there (files could be removed without wordpress noticing)
            $error = \validate_plugin($plugin);
            if (!is_wp_error($error)) {
                return true;
            }
        }

        $error = \validate_plugin($plugin);

        if (is_wp_error($error)) {
            $text =  'Error: Plugin main file has not been found ('.$plugin.').'
                .'<br/>This probably means the main file\'s name does not match the slug.'
                .'<br/>Please check the plugins listing in wp-admin.'
                ."<br>\n"
                .var_export($error->get_error_code(), true).': '
                .var_export($error->get_error_message(), true)
                ."\n";

            throw new RuntimeException($text);
        }

        $error = \activate_plugin($plugin_mainfile);

        if (is_wp_error($error)) {
            $text = 'Error: Plugin has not been activated ('.$plugin.').'
                .'<br/>This probably means the main file\'s name does not match the slug.'
                .'<br/>Check the plugins listing in wp-admin.'
                ."<br/>\n"
                .var_export($error->get_error_code(), true).': '
                .var_export($error->get_error_message(), true)
                ."\n";

            throw new RuntimeException($text);
        }

        return true;
    }

    /**
     * Launches auto-activation of required plugins.
     *
     * @return void
     */
    public function activateRequiredPlugins() : void
    {
        $plugins = $this->getConfig('must-have-plugins');
        foreach ($plugins as $plugin) {
            $error = activate_plugin($plugin);
            if (!empty($error)) {
                echo $error;
            }
        }
    }

    /**
     * Скопировать файлы плагина.
     *
     * @param string $plugin        Директория с плагином (advanced-custom-fields)
     * @param string $pluginsSrcDir Исходная директория, где лежат плагины
     * @param string $wpPluginsDir  Директория с плагинами WP, куда подлежит скопировать.
     *
     * @return void
     */
    public function installPlugin(string $plugin, string $pluginsSrcDir, string $wpPluginsDir)
    {
        $destPath = $wpPluginsDir .  '/' . $plugin;

        try {
            $iterator = new FilesystemIterator($destPath);
        } catch (Exception $e) {
            $this->copyPluginFiles(
                $pluginsSrcDir . '/' . $plugin,
                $destPath
            );

            return;
        }

        $isValid = $iterator->valid();
        if (!$isValid) {
            $this->copyPluginFiles(
                $pluginsSrcDir . '/' . $plugin,
                $destPath
            );
        }
    }

    /**
     * @param string $src
     * @param string $destination
     *
     * @return boolean
     */
    public function copyPluginFiles(string $src, string $destination) : bool
    {
        return $this->copyr($src, $destination);
    }

    /**
     * Скопированы ли уже файлы плагина.
     *
     * @param string $path Путь.
     *
     * @return boolean
     */
    public function hasPluginInstalled(string $path) : bool
    {
        $iterator = new FilesystemIterator($path);

        return !$iterator->valid();
    }

    /**
     * @param array $requiredPlugins
     *
     * @return void
     */
    public function setRequiredPlugins(array $requiredPlugins): void
    {
        $this->requiredPlugins = $requiredPlugins;
    }

    /**
     * Gets runtime config data for a given context.
     *
     * @param string $context What config data needs to be returned?
     *
     * @return array          Runtime config for that context.
     */
    private function getConfig(string $context) : array
    {
        if ('must-have-plugins' === $context) {
            // Array of plugin basenames of plugins that need to be active.
            return $this->requiredPlugins;
        }

        return [];
    }

    /**
     * @param string $source Откуда.
     * @param string $dest   Куда.
     *
     * @return boolean
     */
    private function copyr(string $source, string $dest) : bool
    {
        if (!file_exists($dest)) {
            @mkdir($dest);
        }

        $dir = opendir($source);

        while($file = readdir($dir)) {
            if ($file !== '.' && $file !== '..') {
                if ( is_dir($source . '/' . $file) ) {
                    $this->copyr($source .'/'. $file, $dest .'/'. $file);
                }
                else {
                    copy($source .'/'. $file,$dest .'/'. $file);
                }
            }
        }
        closedir($dir);

        return true;
    }
}
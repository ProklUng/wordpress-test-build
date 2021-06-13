<?php

namespace Prokl\WordpressCi\Base;

use Prokl\WordpressCi\Exceptions\WPAjaxDieContinueException;
use Prokl\WordpressCi\Exceptions\WPAjaxDieStopException;
use WP_Error;

/**
 * Class WordpressableAjaxTestCase
 * @package Prokl\WordpressCi\Base
 *
 * @since 13.06.2021
 * @see https://github.com/wp-phpunit/wp-phpunit/blob/master/includes/testcase-ajax.php
 */
class WordpressableAjaxTestCase extends WordpressableTestCase
{
    /**
     *
     * @var string $_last_response Last Ajax response. This is set via echo -or- wp_die.
     */
    protected $_last_response = '';

    /**
     * @var array $_core_actions_get List of Ajax actions called via GET.
     */
    protected static $_core_actions_get = [
        'fetch-list',
        'ajax-tag-search',
        'wp-compression-test',
        'imgedit-preview',
        'oembed-cache',
        'autocomplete-user',
        'dashboard-widgets',
        'logged-in',
    ];

    /**
     * @var integer $_error_level Saved error reporting level.
     */
    protected $_error_level = 0;

    /**
     * @var array $_core_actions_post List of Ajax actions called via POST.
     */
    protected static $_core_actions_post = [
        'oembed_cache',
        'image-editor',
        'delete-comment',
        'delete-tag',
        'delete-link',
        'delete-meta',
        'delete-post',
        'trash-post',
        'untrash-post',
        'delete-page',
        'dim-comment',
        'add-link-category',
        'add-tag',
        'get-tagcloud',
        'get-comments',
        'replyto-comment',
        'edit-comment',
        'add-menu-item',
        'add-meta',
        'add-user',
        'closed-postboxes',
        'hidden-columns',
        'update-welcome-panel',
        'menu-get-metabox',
        'wp-link-ajax',
        'menu-locations-save',
        'menu-quick-search',
        'meta-box-order',
        'get-permalink',
        'sample-permalink',
        'inline-save',
        'inline-save-tax',
        'find_posts',
        'widgets-order',
        'save-widget',
        'set-post-thumbnail',
        'date_format',
        'time_format',
        'wp-fullscreen-save-post',
        'wp-remove-post-lock',
        'dismiss-wp-pointer',
        'send-attachment-to-editor',
        'heartbeat',
        'nopriv_heartbeat',
        'get-revision-diffs',
        'save-user-color-scheme',
        'update-widget',
        'query-themes',
        'parse-embed',
        'set-attachment-thumbnail',
        'parse-media-shortcode',
        'destroy-sessions',
        'install-plugin',
        'update-plugin',
        'press-this-save-post',
        'press-this-add-category',
        'crop-image',
        'generate-password',
        'save-wporg-username',
        'delete-plugin',
        'search-plugins',
        'search-install-plugins',
        'activate-plugin',
        'update-theme',
        'delete-theme',
        'install-theme',
        'get-post-thumbnail-html',
        'wp-privacy-export-personal-data',
        'wp-privacy-erase-personal-data',
    ];

    /**
     * Sets up the test fixture.
     *
     * Overrides wp_die(), pretends to be Ajax, and suppresses E_WARNINGs.
     */
    protected function setUp() : void
    {
        parent::setUp();

        remove_action('admin_init', '_maybe_update_core');
        remove_action('admin_init', '_maybe_update_plugins');
        remove_action('admin_init', '_maybe_update_themes');

        // Register the core actions.
        foreach (array_merge(self::$_core_actions_get, self::$_core_actions_post) as $action) {
            if (function_exists('wp_ajax_'.str_replace('-', '_', $action))) {
                add_action('wp_ajax_'.$action, 'wp_ajax_'.str_replace('-', '_', $action), 1);
            }
        }

        require_once ABSPATH . '/wp-admin/includes/screen.php';
        require_once ABSPATH . '/wp-admin/includes/class-wp-screen.php';
        require_once ABSPATH . '/wp-admin/includes/template.php';

        add_filter('wp_doing_ajax', '__return_true');
        add_filter('wp_die_ajax_handler', [$this, 'wpDieHandler'], 1, 1);

        set_current_screen('ajax');

        // Clear logout cookies.
        add_action('clear_auth_cookie', [$this, 'logout']);

        // Suppress warnings from "Cannot modify header information - headers already sent by".
        $this->_error_level = error_reporting();
        error_reporting($this->_error_level & ~E_WARNING);
    }

    /**
     * @inheritDoc
     *
     * Resets $_POST, removes the wp_die() override, restores error reporting.
     */
    protected function tearDown() : void
    {
        parent::tearDown();
        $_POST = [];
        $_GET = [];
        unset($GLOBALS['post']);
        unset($GLOBALS['comment']);
        remove_filter('wp_die_ajax_handler', [$this, 'wpDieHandler'], 1);
        remove_action('clear_auth_cookie', [$this, 'logout']);
        error_reporting($this->_error_level);
        set_current_screen('front');
    }

    /**
     * Handler for wp_die().
     *
     * Save the output for analysis, stop execution by throwing an exception.
     *
     * Error conditions (no output, just die) will throw <code>WPAjaxDieStopException( $message )</code>.
     * You can test for this with:
     * <code>
     * $this->expectException( 'WPAjaxDieStopException' );
     * $this->expectExceptionMessage( 'something contained in $message' );
     * </code>
     *
     * Normal program termination (wp_die called at the end of output) will throw <code>WPAjaxDieContinueException( $message )</code>.
     * You can test for this with:
     * <code>
     * $this->expectException( 'WPAjaxDieContinueException' );
     * $this->expectExceptionMessage( 'something contained in $message' );
     * </code>
     *
     * @param string | WP_Error $message The message to set.
     *
     * @return void
     *
     * @throws WPAjaxDieStopException     Thrown to stop further execution.
     * @throws WPAjaxDieContinueException Thrown to stop execution of the Ajax function,
     *                                    but continue the unit test.
     */
    protected function wpDieHandler($message) : void
    {
        $this->_last_response .= ob_get_clean();

        if ($this->_last_response === '') {
            if (is_scalar($message)) {
                throw new WPAjaxDieStopException((string)$message);
            } else {
                throw new WPAjaxDieStopException('0');
            }
        } else {
            throw new WPAjaxDieContinueException($message);
        }
    }

    /**
     * Mimics the Ajax handling of admin-ajax.php.
     *
     * Captures the output via output buffering, and if there is any,
     * stores it in $this->_last_response.
     *
     * @param string $action The action to handle.
     *
     * @return void
     */
    protected function _handleAjax($action) : void
    {
        // Start output buffering.
        ini_set('implicit_flush', false);
        ob_start();

        // Build the request.
        $_POST['action'] = $action;
        $_GET['action'] = $action;
        $_REQUEST = array_merge($_POST, $_GET);

        // Call the hooks.
        do_action('admin_init');
        do_action('wp_ajax_'.$_REQUEST['action'], null);

        // Save the output.
        $buffer = ob_get_clean();
        if (!empty($buffer)) {
            $this->_last_response = $buffer;
        }
    }

    /**
     * Clears login cookies, unsets the current user.
     *
     * @return void
     */
    protected function logout() : void
    {
        unset($GLOBALS['current_user']);
        $cookies = [AUTH_COOKIE, SECURE_AUTH_COOKIE, LOGGED_IN_COOKIE, USER_COOKIE, PASS_COOKIE];
        foreach ($cookies as $c) {
            unset($_COOKIE[$c]);
        }
    }
}

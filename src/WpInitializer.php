<?php

namespace Prokl\WordpressCi;

use Faker\Factory;
use Faker\Generator;
use Prokl\WordpressCi\Provider\Picsum;
use Prokl\WordpressCi\Provider\WordPress;

/**
 * Class Loader
 * @package Prokl\WordpressCi
 *
 * @since 14.05.2021
 */
class WpInitializer
{
    /**
     * @var Generator $generator
     */
    private $generator;

    /**
     * Loader constructor.
     */
    public function __construct()
    {
        // Set locale
        $this->generator = Factory::create(get_locale());
        // Add provider
        $this->generator->addProvider(new WordPress($this->generator));
        $this->generator->addProvider(new Picsum($this->generator));

        // Set current user as the first administrator
        // Needed to get the right permissions for persisting some objects (post terms for example)
        $administrators = get_users(['role' => 'administrator']);
        if ($administrators) {
            $ids = wp_list_pluck($administrators, 'ID');
            wp_set_current_user(min($ids));
        }

        // Remove revisions, it duplicates posts when they are updated
        remove_action('post_updated', 'wp_save_post_revision');

        // Disable user notifications
        add_filter('send_password_change_email', '__return_false', PHP_INT_MAX);
        add_filter('send_email_change_email', '__return_false', PHP_INT_MAX);

        // Disable comment notifications
        add_filter('notify_post_author', '__return_false', PHP_INT_MAX);
        add_filter('notify_moderator', '__return_false', PHP_INT_MAX);
    }

    /**
     * @return Generator
     */
    public function getGenerator(): Generator
    {
        return $this->generator;
    }

}
<?php

namespace Prokl\WordpressCi\FixtureGenerator\Entity;

use WP_User_Query;

/**
 * Class User
 * @package Prokl\WordpressCi\FixtureGenerator\Entity
 */
class User extends Entity
{
    public $ID;
    public $user_pass;
    public $user_login;
    public $user_nicename;
    public $user_url;
    public $user_email;
    public $display_name;
    public $nickname;
    public $first_name;
    public $last_name;
    public $description;
    public $rich_editing;
    public $user_registered;
    public $role;
    public $jabber;
    public $aim;
    public $yim;
    public $comment_shortcuts;
    public $admin_color;
    public $use_ssl;
    public $show_admin_bar_front;
    public $acf;

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $this->ID = wp_insert_user([
            'user_login' => sprintf('user-%s', uniqid()),
            'user_pass'  => 12345,
        ]);
        update_user_meta($this->ID, '_fake', true);

        return $this->ID;
    }

    /**
     * {@inheritdoc}
     */
    public function persist()
    {
        if (!$this->ID) {
            return false;
        }

        if (!$this->user_nicename) {
            $this->user_nicename = sanitize_title(mb_substr($this->user_login, 0, 50));
        }
        if (!$this->display_name) {
            $this->display_name = $this->user_login;
        }

        $user_id = wp_update_user($this->getData());
        if (is_wp_error($user_id)) {
            wp_delete_user($this->ID);
            $this->setCurrentId(false);

            return false;
        }

        // Only way to update user login
        global $wpdb;
        $wpdb->update($wpdb->users, ['user_login' => $this->user_login], ['ID' => $this->ID]);

        // Save meta
        $meta = $this->getMetaData();
        foreach ($meta as $meta_key => $meta_value) {
            update_user_meta($this->ID, $meta_key, $meta_value);
        }

        // Save ACF fields
        if (class_exists('acf') && !empty($this->acf) && is_array($this->acf)) {
            foreach ($this->acf as $name => $value) {
                $field = acf_get_field($name);
                update_field($field['key'], $value, 'user_' . $this->ID);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($id)
    {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare("
            SELECT ID
            FROM {$wpdb->users}
            WHERE ID = %d
            LIMIT 1
        ", absint($id)));
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentId($id)
    {
        $this->ID = $id;
    }

    /**
     * {@inheritdoc}
     */
    public static function delete()
    {
        $query = new WP_User_Query([
            'fields'     => 'ID',
            'meta_query' => [
                [
                    'key'   => '_fake',
                    'value' => true,
                ],
            ],
        ]);

        if (empty($query->results)) {
            return false;
        }

        foreach ($query->results as $id) {
            wp_delete_user($id);
        }
        $count = count($query->results);

        return true;
    }
}

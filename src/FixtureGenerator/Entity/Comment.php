<?php

namespace Prokl\WordpressCi\FixtureGenerator\Entity;

use WP_Comment_Query;

/**
 * Class Comment
 * @package Prokl\WordpressCi\FixtureGenerator\Entity
 */
class Comment extends Entity
{
    public $comment_ID;
    public $comment_author;
    public $comment_author_email;
    public $comment_author_url;
    public $comment_content;
    public $comment_date;
    public $comment_parent;
    public $comment_post_ID;
    public $user_id;
    public $comment_agent;
    public $comment_author_IP;
    public $comment_approved;
    public $comment_karma;
    public $comment_meta;

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $this->comment_ID = wp_insert_comment([
            'comment_content' => sprintf('comment-%s', uniqid()),
        ]);
        update_comment_meta($this->comment_ID, '_fake', true);

        return $this->comment_ID;
    }

    /**
     * {@inheritdoc}
     */
    public function persist()
    {
        if (!$this->comment_ID) {
            return false;
        }

        // Update entity
        $comment_id = wp_update_comment($this->getData());

        // Handle errors
        if (empty($comment_id)) {
            wp_delete_comment($this->comment_ID, true);
            $this->setCurrentId(false);

            return false;
        }

        // Save meta
        $meta = $this->getMetaData();
        foreach ($meta as $meta_key => $meta_value) {
            update_comment_meta($this->comment_ID, $meta_key, $meta_value);
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
            SELECT comment_ID
            FROM {$wpdb->comments}
            WHERE comment_ID = %d
            LIMIT 1
        ", absint($id)));
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentId($id)
    {
        $this->comment_ID = $id;
    }

    /**
     * {@inheritdoc}
     */
    public static function delete()
    {
        $query = new WP_Comment_Query([
            'fields'     => 'ids',
            'meta_query' => [
                [
                    'key'   => '_fake',
                    'value' => true,
                ],
            ],
        ]);

        if (empty($query->comments)) {
            return false;
        }

        foreach ($query->comments as $id) {
            wp_delete_comment($id, true);
        }
        $count = count($query->comments);
    }
}

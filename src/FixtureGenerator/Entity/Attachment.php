<?php

namespace Prokl\WordpressCi\FixtureGenerator\Entity;

use WP_Query;

/**
 * Class Attachment
 * @package Prokl\WordpressCi\FixtureGenerator\Entity
 */
class Attachment extends Post
{
    public $file;
    public $post_type   = 'attachment';
    public $post_status = 'inherit';

    /**
     * {@inheritdoc}
     */
    public function create() : int
    {
        $this->ID = wp_insert_post([
            'post_title'  => sprintf('attachment-%s', uniqid()),
            'post_type'   => $this->post_type,
            'post_status' => $this->post_status,
        ]);
        update_post_meta($this->ID, '_fake', true);

        return $this->ID;
    }

    /**
     * {@inheritdoc}
     */
    public function persist() : int
    {
        include_once ABSPATH.'wp-admin/includes/image.php';
        include_once ABSPATH.'wp-admin/includes/media.php';

        if (!$this->ID || empty($this->file)) {
            if (is_file($this->file)) {
                @unlink($this->file);
            }

            wp_delete_attachment($this->ID, true);

            return 0;
        }

        $file_name  = basename($this->file);

        $upload_dir = wp_upload_dir();

        // Image has been saved to sys temp dir
        if (strpos($this->file, $upload_dir['basedir']) === false) {
            $upload = wp_upload_bits($file_name, null, file_get_contents($this->file));

            if ($upload['error']) {
                wp_delete_attachment($this->ID, true);

                $this->setCurrentId(false);

                return 0;
            } else {
                $this->file = $upload['file'];
            }
        }

        $file_type = wp_check_filetype($file_name);

        // Set required attachment properties
        $this->post_mime_type = $file_type['type'];
        $this->guid           = $upload_dir['url'] . '/' . $file_name;

        // Set post title from file name
        if (empty($this->post_title)) {
            $this->post_title = sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME));
        }

        // Update entity
        $attachment_id = wp_insert_attachment($this->getData(), $this->file);

        // Update guid and slug (can't be updated once post is created)
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            [
                'guid'      => $this->guid,
                'post_name' => sanitize_title($this->post_title),
            ],
            [
                'ID' => $this->ID,
            ]
        );

        // Handle errors
        if (empty($attachment_id)) {
            wp_delete_attachment($this->ID, true);
            $this->setCurrentId(false);

            return 0;
        }

        // Generate attachment metadata
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $this->file);

        // Assign metadata to attachment
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return parent::persist();
    }

    /**
     * {@inheritdoc}
     */
    public static function delete() : int
    {
        $query = new WP_Query([
            'fields'     => 'ids',
            'meta_query' => [
                [
                    'key'   => '_fake',
                    'value' => true,
                ],
            ],
            'post_status'    => 'any',
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
        ]);

        if (empty($query->posts)) {
            return 0;
        }

        foreach ($query->posts as $id) {
            wp_delete_attachment($id, true);
        }

        return count($query->posts);
    }
}

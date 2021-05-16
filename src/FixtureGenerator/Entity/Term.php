<?php

namespace Prokl\WordpressCi\FixtureGenerator\Entity;

use WP_Term_Query;

/**
 * Class Term
 * @package Prokl\WordpressCi\FixtureGenerator\Entity
 */
class Term extends Entity
{
    public $term_id;
    public $name;
    public $slug;
    public $taxonomy = 'category';
    public $description;
    public $parent;
    public $acf;

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $term = wp_insert_term(sprintf('term-%s', uniqid()), $this->taxonomy);
        if (is_wp_error($term)) {
            $this->setCurrentId(false);

            return $term;
        }
        $this->term_id = $term['term_id'];
        update_term_meta($this->term_id, '_fake', true);

        return $this->term_id;
    }

    /**
     * {@inheritdoc}
     */
    public function persist()
    {
        if (!$this->term_id) {
            return false;
        }

        // Change taxonomy before updating term
        global $wpdb;
        $wpdb->update(
            $wpdb->term_taxonomy,
            [
                'taxonomy' => $this->taxonomy,
            ],
            [
                'taxonomy' => 'category',
                'term_id'  => $this->term_id,
            ]
        );

        // Update temp slug
        $data = $this->getData();
        if (!$this->slug && $this->name) {
            $data['slug'] = sanitize_title($this->name);
        }

        $term_id = wp_update_term($this->term_id, $this->taxonomy, $data);

        if (is_wp_error($term_id)) {
            wp_delete_term($this->term_id, $this->taxonomy);
            $this->setCurrentId(false);

            return false;
        }

        // Save meta
        $meta = $this->getMetaData();
        foreach ($meta as $meta_key => $meta_value) {
            update_term_meta($this->term_id, $meta_key, $meta_value);
        }

        // Save ACF fields
        if (class_exists('acf') && !empty($this->acf) && is_array($this->acf)) {
            foreach ($this->acf as $name => $value) {
                $field = acf_get_field($name);
                update_field($field['key'], $value, $this->taxonomy . '_' . $this->term_id);
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
            SELECT term_id
            FROM {$wpdb->terms}
            WHERE term_id = %d
            LIMIT 1
        ", absint($id)));
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentId($id)
    {
        $this->term_id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public static function delete()
    {
        $query = new WP_Term_Query([
            'fields'     => 'ids',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'   => '_fake',
                    'value' => true,
                ],
            ],
        ]);

        if (empty($query->terms)) {
            return false;
        }

        foreach ($query->terms as $id) {
            $term = get_term($id);
            if (!isset($term->taxonomy)) {
                continue;
            }
            // Nav Menu's are handled within NavMenu.
            if ($term->taxonomy === 'nav_menu') {
                continue;
            }
            wp_delete_term($id, $term->taxonomy);
        }
        $count = count($query->terms);

        return true;
    }
}

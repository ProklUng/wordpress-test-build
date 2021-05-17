<?php

namespace Prokl\WordpressCi\FixtureGenerator\Entity;

use WP_Term_Query;

/**
 * Class NavMenu
 * @package Prokl\WordpressCi\FixtureGenerator\Entity
 */
class NavMenu extends Term
{
    public $taxonomy = 'nav_menu';
    public $locations;

    public function persist()
    {
        parent::persist();
        if (!empty($this->locations) && is_array($this->locations)) {
            $locations = array_fill_keys($this->locations, $this->term_id);
            set_theme_mod('nav_menu_locations', $locations);
        }

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public static function delete() : int
    {
        $query = new WP_Term_Query([
            'taxonomy'   => 'nav_menu',
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
            return 0;
        }

        foreach ($query->terms as $id) {
            $term = get_term($id);
            if (!isset($term->taxonomy)) {
                continue;
            }
            wp_delete_term($id, $term->taxonomy);
        }

        return count($query->terms);
    }
}

<?php

namespace Prokl\WordpressCi\Provider;

use Exception;
use Faker\Provider\Base;
use Faker\Provider\File;
use InvalidArgumentException;
use WP_Query;

/**
 * Class WordPress
 * @package Prokl\WordpressCi\Provider
 */
class WordPress extends Base
{
    /**
     * Get permalink.
     *
     * @param integer $id ID поста.
     *
     * @return mixed
     */
    public function permalink(int $id)
    {
        return get_permalink($id);
    }

    /**
     * Фэйковый ID поста.
     *
     * @return integer
     */
    public function fakeId() : int
    {
        return mt_rand(1000000000, 1000000000000);
    }

    /**
     * Get a file content.
     *
     * @param string $file Файл.
     *
     * @return string
     */
    public function fileContent(string $file) : string
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException(sprintf('File %s does not exist.', $file));
        }

        return file_get_contents($file);
    }

    /**
     * Get files.
     *
     * @param  string         $src
     * @param  string|boolean $target
     * @param  boolean        $fullpath
     *
     * @return string
     */
    public function fileIn(string $src = '/tmp', $target = false, bool $fullpath = true) : string
    {
        if (false === $target) {
            $target = $this->uploadDir();
        }

        return File::file($src, $target, $fullpath);
    }

    /**
     * Get upload dir.
     *
     * @return string
     */
    public function uploadDir() : string
    {
        $upload_dir = wp_upload_dir();

        if (isset($upload_dir['path']) && is_dir($upload_dir['path']) && is_writable($upload_dir['path'])) {
            return $upload_dir['path'];
        }

        return sys_get_temp_dir();
    }

    /**
     * Get a random post ID.
     *
     * @param array|string $args
     *
     * @return integer|boolean
     */
    public function postId($args = [])
    {
        $query_args = array_merge(wp_parse_args($args), [
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        $posts = get_posts($query_args);
        if (empty($posts)) {
            return false;
        }

        return absint(self::randomElement($posts));
    }

    /**
     * Get a random attachment ID.
     *
     * @param array|string $args
     *
     * @return integer|boolean
     */
    public function attachmentId($args = [])
    {
        $defaults = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => ['image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/tiff', 'image/x-icon'],
        ];

        return $this->postId(array_merge(wp_parse_args($args), $defaults));
    }

    /**
     * ID случайной картинки.
     *
     * @return integer
     *
     * @since 26.12.2020
     */
    public function randomIdPicture() : int
    {
        $args = [
            'post_type'      => 'attachment',
            'orderby'        => 'rand',
            'posts_per_page' => 1,
            'post_status' => 'published',
            'nopaging' => false,
            'no_found_rows' => true,
        ];

        $query = new WP_Query($args);

        $result = $query->query($args);
        wp_reset_query();

        return current($result)->ID;
    }

    /**
     * URL случайной картинки.
     *
     * @return string
     *
     * @since 26.12.2020
     */
    public function getUrlRandomPicture() : string
    {
        $arData = wp_get_attachment_image_src($this->randomIdPicture(), 'full');

        return $arData[0] ?? '';
    }

    /**
     * ID случайного поста средствами Wp.
     *
     * @param string $postType Тип поста. По умолчанию - post.
     *
     * @return integer
     */
    public function randomIdPostWp(string $postType = 'post'): int
    {
        $args = [
            'post_type' => $postType,
            'orderby' => 'rand',
            'posts_per_page' => 1,
            'post_status' => 'published',
            'nopaging' => false,
            'no_found_rows' => true,
        ];

        $query = new WP_Query();
        $result = $query->query($args);
        wp_reset_query();

        return current($result)->ID;
    }

    /**
     * Массовое получение ID случайных постов.
     *
     * @param integer $qty  Количество.
     * @param string  $type Тип поста. По умолчанию - post.
     *
     * @return array
     *
     * @since 26.12.2020
     */
    public function getRandomIdPostMassive(int $qty, string $type = 'post') : array
    {
        $result = [];
        for ($i = 0; $i<$qty; $i++) {
            $result[] = $this->randomIdPostWp($type);
        }

        return $result;
    }

    /**
     * ID случайной страницы.
     *
     * @return integer
     */
    public function randomIdPage(): int
    {
        return $this->randomIdPostWp('page');
    }

    /**
     * Случайный ID поста в категории средствами WP.
     *
     * @param string $categorySlug Метка категории.
     *
     * @return integer
     */
    public function randomIdPostInCategoryWp(string $categorySlug): int
    {
        $args = [
            'post_type' => 'post',
            'orderby' => 'rand',
            'posts_per_page' => 1,
            'post_status' => 'published',
            'nopaging' => false,
            'no_found_rows' => true,
            'category_name' => $categorySlug,
        ];

        $query = new WP_Query();

        wp_reset_query();

        return current($query->query($args))->ID;
    }

    /**
     * ID элемента с контентом.
     *
     * @param integer $minLength Минимальная длина поста.
     * @param string  $postType  Тип поста. По умолчанию - post.
     *
     * @return integer
     */
    public function getRandomIdPostWithContentWp(
        int $minLength = 650,
        string $postType = 'post'
    ): int {
        $args = [
            'post_type' => $postType,
            'orderby' => 'rand',
            'posts_per_page' => 1,
            'post_status' => 'published',
            'nopaging' => false,
            'no_found_rows' => true,
        ];

        $query = new WP_Query();

        do {
            $result = current($query->query($args));
        } while (strlen($result->post_content) < $minLength);

        wp_reset_query();

        return $result->ID;
    }

    /**
     * ID элемента без контента.
     *
     * @param string $postType Тип поста. По умолчанию - post.
     *
     * @return integer
     *
     * @since 26.12.2020
     */
    public function randomIdPostWithoutContentWp(string $postType = 'post'): int
    {
        $args = [
            'post_type' => $postType,
            'orderby' => 'rand',
            'posts_per_page' => 1,
            'post_status' => 'published',
            'nopaging' => false,
            'no_found_rows' => true,
        ];

        $query = new WP_Query();

        do {
            $result = current($query->query($args));
        } while ($result->post_content !== '');

        wp_reset_query();

        return $result->ID;
    }

    /**
     * ID поста с картинкой.
     *
     * @param string $postType   Тип поста.
     * @param array  $categories Категории.
     *
     * @return integer
     */
    public static function randomIdPostPictureWp(
        string $postType = 'post',
        array $categories = []
    ): int {
        $args = [
            'post_type' => $postType,
            'orderby' => 'rand',
            'posts_per_page' => 1,
            'post_status' => 'published',
            'nopaging' => false,
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => '_thumbnail_id',
                ],
            ],
            'category__in' => $categories
        ];

        $query = new WP_Query();
        $result = $query->query($args);

        wp_reset_query();

        return current($result)->ID;
    }

    /**
     * ID поста без картинки.
     *
     * @param string $postType Тип поста.
     *
     * @return integer
     */
    public function getRandomIdPostWithoutPictureWp(string $postType = 'post'): int
    {
        $args = [
            'post_type' => $postType,
            'orderby' => 'rand',
            'posts_per_page' => 1,
            'post_status' => 'published',
            'nopaging' => false,
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => '_thumbnail_id',
                    'value' => '?',
                    'compare' => 'NOT EXISTS'
                ],
            ],
        ];

        $query = new WP_Query();
        $result = $query->query($args);

        return current($result)->ID;
    }

    /**
     * ID случайной категории.
     *
     * @return integer
     * @throws Exception
     */
    public function randomCategoryId(): int
    {
        $categories = get_categories();
        do {
            $index = random_int(0, count($categories));

            $randomCategory = $categories[$index] ?? null;
        } while ($randomCategory->cat_ID === null);

        return $randomCategory->cat_ID;
    }

    /**
     * Случайный пост по мета полю.
     *
     * @param string $metaKey   Мета поле.
     * @param mixed  $metaValue Значение.
     *
     * @return integer
     *
     * @since 26.12.2020
     */
    public function getRandomIdByMetaValue(
        string $metaKey,
        $metaValue
    ): int {
        $args = [
            'post_type' => 'post',
            'orderby' => 'rand',
            'posts_per_page' => 1,
            'post_status' => 'published',
            'nopaging' => false,
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => $metaKey,
                    'value' => $metaValue,
                    'compare' => '='
                ],
            ],
        ];

        $query = new WP_Query();
        $result = $query->query($args);

        return current($result)->ID;
    }

    /**
     * Случайный пост по мета полю. Отрицание.
     *
     * @param string $metaKey Мета поле.
     *
     * @return integer
     *
     * @since 26.12.2020
     */
    public function getRandomIdByNotMetaValue(
        string $metaKey
    ): int {
        $args = [
            'post_type' => 'post',
            'orderby' => 'rand',
            'posts_per_page' => 1,
            'post_status' => 'published',
            'nopaging' => false,
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => $metaKey,
                    'compare' => 'NOT EXISTS'
                ],
            ],
        ];

        $query = new WP_Query();
        $result = $query->query($args);

        return current($result)->ID;
    }

    /**
     * Слаг тэга с картинками.
     *
     * @return string
     *
     * @since 26.12.2020
     */
    public function tagWithTagPicture() : string
    {
        return get_post_field('post_title', $this->randomIdPostPictureWp( 'tagspicture'));
    }

    /**
     * Случайный тэг без картинки.
     *
     * @return string
     * @throws Exception
     *
     * @since 26.12.2020
     */
    public function getRandomTagSlugWithoutPicture() : string
    {
        do {
            $slug = $this->randomTagSlug();

            $args = [
                'post_type' => 'tagspicture',
                'orderby' => 'rand',
                'posts_per_page' => 1,
                'post_status' => 'published',
                'post_title' => $slug,
                'nopaging' => false,
                'no_found_rows' => true,
                'meta_query' => [
                    [
                        'key' => '_thumbnail_id',
                    ],
                ],
            ];

            $query = new WP_Query();
            $result = $query->query($args);

            wp_reset_query();
        } while (current($result)->ID === null);

        return $slug;
    }

    /**
     * Случайный тэг.
     *
     * @return string
     * @throws Exception
     */
    public function randomTagSlug() : string
    {
        $alltags = get_tags();

        return $alltags[random_int(0, count($alltags))]->slug;
    }

    /**
     * Случайный ID поста с заполненным ACF полем video_element.
     *
     * @return integer
     *
     * @since 29.12.2020
     */
    public function idPostWithAcfVideo() : int
    {
        $args = [
            'post_type' => 'post',
            'orderby' => 'rand',
            'posts_per_page' => 1,
            'post_status' => 'published',
            'nopaging' => false,
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => 'video_element',
                    'value' => '',
                    'compare' => '<>'
                ],
            ],
        ];

        $query = new WP_Query();
        $result = $query->query($args);

        wp_reset_query();

        return current($result)->ID ?: 0;
    }

    /**
     * Get a random user ID.
     *
     * @param array|string $args Аргументы.
     *
     * @return integer|boolean
     */
    public function userId($args = [])
    {
        $query_args = array_merge(wp_parse_args($args), [
            'number' => -1,
            'fields' => ['ID'],
        ]);

        $users = get_users($query_args);

        if (empty($users)) {
            return false;
        }

        return absint(self::randomElement(wp_list_pluck($users, 'ID')));
    }

    /**
     * Get a random term ID.
     *
     * @param array|string $args
     *
     * @return integer|boolean
     */
    public function termId($args = [])
    {
        $query_args = array_merge(wp_parse_args($args, ['taxonomy' => 'category']), [
            'number'     => 0,
            'fields'     => 'ids',
            'hide_empty' => false,
        ]);

        $terms = get_terms($query_args);

        if (empty($terms) || is_wp_error($terms)) {
            return false;
        }

        return absint(self::randomElement($terms));
    }
}

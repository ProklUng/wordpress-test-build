<?php

namespace Prokl\WordpressCi\Helpers;

use Faker\Factory;
use Faker\Generator;
use Prokl\WordpressCi\Provider\Picsum;
use Prokl\WordpressCi\Provider\WordPress;

/**
 * Class Helper
 * @package Prokl\WordpressCi\Helpers
 *
 * @since 16.04.2021
 */
class Helper
{
    /**
     * @return Generator
     */
    public static function getFaker() : Generator
    {
        $faker = Factory::create(get_locale());
        // Add provider
        $faker->addProvider(new WordPress($faker));
        $faker->addProvider(new Picsum($faker));

        return $faker;
    }
}
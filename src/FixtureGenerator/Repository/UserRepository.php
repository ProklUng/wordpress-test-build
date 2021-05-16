<?php

namespace Prokl\WordpressCi\FixtureGenerator\Repository;

use Faker\Factory;
use Faker\Generator;
use Prokl\WordpressCi\FixtureGenerator\Entity\Post;
use Prokl\WordpressCi\FixtureGenerator\Entity\Term;
use Prokl\WordpressCi\FixtureGenerator\Entity\User;
use Prokl\WordpressCi\Provider\Picsum;
use Prokl\WordpressCi\Provider\WordPress;

/**
 * Class UserRepository
 * @package Prokl\WordpressCi\FixtureGenerator\Repository
 *
 * @since 16.05.2021
 */
class UserRepository
{
    /**
     * @var Generator $faker Фэйкер.
     */
    private $faker;

    /**
     * UserRepository constructor.
     *
     * @param Generator $faker
     */
    public function __construct(Generator $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Статический конструктор.
     *
     * @param integer $count   Количество.
     * @param array   $payload Нагрузка.
     *
     * @return void
     */
    public static function create(int $count = 1, array $payload = []) : void
    {
        // Set locale
        $generator = Factory::create(get_locale());

        $self = new static($generator);
        $self->random($count, $payload);
    }

    /**
     * Создать заданное количество постов.
     *
     * @param integer $count   Количество.
     * @param array   $payload
     * @return void
     */
    public function random(int $count = 1, array $payload = [])
    {
        for ($i = 0; $i <= $count; $i++) {
            $userManager = new User();

            $userManager->user_login = $this->faker->userName;
            $userManager->user_nicename = $this->faker->name;
            $userManager->user_email = $this->faker->email;
            $userManager->display_name = $this->faker->name;
            $userManager->first_name = $this->faker->firstName;
            $userManager->last_name = $this->faker->lastName;
            $userManager->description = $this->faker->realText(250);
            $userManager->description = $this->faker->realText(250);

            if (array_key_exists('acf', $payload)) {
                $userManager->acf = $payload['acf'];
            }

            $userManager->create();
            $userManager->persist();
        }
    }

    /**
     * Дата в WP формате.
     *
     * @return string
     */
    private function getDatePost() : string
    {
        $date = $this->faker->dateTimeThisYear();
        $date_string = $date->format('Y-m-d H:i:s');
        $date_stamp = strtotime($date_string);

        return (string)date("Y-m-d H:i:s", $date_stamp);
    }
}
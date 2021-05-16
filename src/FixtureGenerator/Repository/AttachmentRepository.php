<?php

namespace Prokl\WordpressCi\FixtureGenerator\Repository;

use Faker\Factory;
use Faker\Generator;
use Prokl\WordpressCi\FixtureGenerator\Entity\Attachment;
use Prokl\WordpressCi\Provider\Picsum;
use Prokl\WordpressCi\Provider\WordPress;

/**
 * Class AttachmentRepository
 * @package Prokl\WordpressCi\FixtureGenerator\Repository
 *
 * @since 16.05.2021
 */
class AttachmentRepository
{

    /**
     * @var Generator $faker Фэйкер.
     */
    private $faker;

    /**
     * PostRepository constructor.
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
        // Add provider
        $generator->addProvider(new WordPress($generator));
        $generator->addProvider(new Picsum($generator));

        $self = new static($generator);
        $self->random($count, $payload);
    }

    /**
     * Создать заданное количество постов.
     *
     * @param integer $count   Количество.
     * @param array   $payload Дополнительные параметры.
     *
     * @return void
     */
    public function random(int $count = 1, array $payload = [])
    {
        for ($i = 0; $i <= $count; $i++) {
            $attachment = new Attachment();
            $attachment->file = $this->faker->picsum();
            $attachment->post_author = $this->faker->userId();
            $attachment->post_date = $this->getDatePost();
            $attachment->post_name = $this->faker->slug();
            $attachment->post_title = $this->faker->realText(150);

            if (array_key_exists('acf', $payload)) {
                $attachment->acf = $payload['acf'];
            }

            $attachment->create();
            $attachment->persist();
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
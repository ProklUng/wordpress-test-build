<?php

namespace Prokl\WordpressCi\FixtureGenerator\Repository;

use Faker\Factory;
use Faker\Generator;
use Prokl\WordpressCi\FixtureGenerator\Entity\Term;
use Prokl\WordpressCi\Provider\Picsum;
use Prokl\WordpressCi\Provider\WordPress;

/**
 * Class TermRepository
 * @package Prokl\WordpressCi\FixtureGenerator\Repository
 *
 * @since 15.05.2021
 */
class TermRepository
{
    /**
     * @var Term $termManager
     */
    private $termManager;

    /**
     * @var Generator $faker Фэйкер.
     */
    private $faker;

    private $taxonomy = 'category';

    /**
     * TermRepository constructor.
     *
     * @param Term      $termManager
     * @param Generator $faker
     */
    public function __construct(Term $termManager, Generator $faker)
    {
        $this->termManager = $termManager;
        $this->faker = $faker;
    }

    /**
     * Статический конструктор.
     *
     * @param integer $count
     * @param string  $taxonomy
     *
     * @return void
     */
    public static function create(int $count = 1, string $taxonomy = 'category') : void
    {
        // Set locale
        $generator = Factory::create(get_locale());
        // Add provider
        $generator->addProvider(new WordPress($generator));
        $generator->addProvider(new Picsum($generator));

        $self = new static(new Term, $generator);
        $self->setTaxonomy($taxonomy);

        $self->random($count);
    }

    /**
     * Создать заданное количество terms.
     *
     * @param integer $count Количество.
     *
     * @return void
     */
    public function random(int $count = 1)
    {
        $this->termManager->name = $this->taxonomy;
        for ($i = 0; $i <= $count; $i++) {
            $this->termManager->slug = $this->faker->slug;
            $this->termManager->description = $this->faker->text(100);
            $this->termManager->create();
            $this->termManager->persist();
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function setTaxonomy(string $name): void
    {
        $this->taxonomy = $name;
    }
}
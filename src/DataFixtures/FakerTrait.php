<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;

/**
 * Trait providing access to Faker instance.
 */
trait FakerTrait
{
    /**
     * Get a configured Faker instance.
     */
    public static function getFaker(): Generator
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(1337);

        return $faker;
    }
}

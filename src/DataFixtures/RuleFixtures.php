<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ComputedMeasure;
use App\Entity\Rule;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Scoring rules: the default ones also created by the migration (the fixtures
 * truncate the table), then other free rules of the referee.
 */
class RuleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $computedRules = [
            [ComputedMeasure::ValidatedSquares, 100],
            [ComputedMeasure::FourSquareLines, 100],
            [ComputedMeasure::FiveSquareLines, 200],
        ];
        foreach ($computedRules as [$computedMeasure, $coefficient]) {
            $manager->persist(new Rule()
                ->setComputedMeasure($computedMeasure)
                ->setCoefficient($coefficient));
        }

        $freeRules = [
            ['Cases dévoilées à la demande', -50],
            ['Jours passés d\'affilée', 100],
            ['Argent maximum ($) en 3 shifts', 1],
        ];
        foreach ($freeRules as [$measure, $coefficient]) {
            $manager->persist(new Rule()
                ->setMeasure($measure)
                ->setCoefficient($coefficient));
        }

        $manager->flush();
    }
}

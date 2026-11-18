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
    public const int RULE_COUNT = 6;

    // Rules numbered from 1 in the order below: computed rules, then free rules.
    public static function reference(int $number): string
    {
        return 'rule_' . $number;
    }

    public function load(ObjectManager $manager): void
    {
        $computedRules = [
            [ComputedMeasure::ValidatedSquares, 100],
            [ComputedMeasure::FourSquareLines, 100],
            [ComputedMeasure::FiveSquareLines, 200],
        ];
        $number = 0;
        foreach ($computedRules as [$computedMeasure, $coefficient]) {
            $rule = new Rule()
                ->setComputedMeasure($computedMeasure)
                ->setCoefficient($coefficient);
            $manager->persist($rule);
            $this->addReference(self::reference(++$number), $rule);
        }

        $freeRules = [
            ['Cases dévoilées à la demande', -50],
            ['Jours passés d\'affilée', 100],
            ['Argent maximum ($) en 3 shifts', 1],
        ];
        foreach ($freeRules as [$measure, $coefficient]) {
            $rule = new Rule()
                ->setMeasure($measure)
                ->setCoefficient($coefficient);
            $manager->persist($rule);
            $this->addReference(self::reference(++$number), $rule);
        }

        $manager->flush();
    }
}

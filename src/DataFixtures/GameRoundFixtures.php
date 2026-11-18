<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\GameRound;
use App\Entity\Rule;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixture for loading game rounds data: every round applies the computed
 * rules, and a round more free rules than the previous one.
 */
class GameRoundFixtures extends Fixture implements DependentFixtureInterface
{
    public const int SEASON_COUNT = 1;
    public const int ROUND_COUNT = 4;

    public static function reference(int $season, int $round): string
    {
        return 'game_round_' . $season . '_' . $round;
    }

    /**
     * Load game rounds data.
     */
    public function load(ObjectManager $manager): void
    {
        for ($season = 1; $season <= self::SEASON_COUNT; ++$season) {
            for ($round = 1; $round <= self::ROUND_COUNT; ++$round) {
                $gameRound = new GameRound()
                    ->setSeason($season)
                    ->setNumber($round);
                $ruleCount = min(RuleFixtures::RULE_COUNT, 2 + $round);
                for ($rule = 1; $rule <= $ruleCount; ++$rule) {
                    $gameRound->addRule($this->getReference(RuleFixtures::reference($rule), Rule::class));
                }
                $manager->persist($gameRound);
                $this->addReference(self::reference($season, $round), $gameRound);
            }
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [RuleFixtures::class];
    }
}

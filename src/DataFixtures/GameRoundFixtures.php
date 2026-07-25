<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\GameRound;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixture for loading game rounds data.
 */
class GameRoundFixtures extends Fixture
{
    public const int SEASON_COUNT = 1;
    public const int ROUND_COUNT = 4;

    /**
     * Load game rounds data.
     */
    public function load(ObjectManager $manager): void
    {
        for ($season = 1; $season <= self::SEASON_COUNT; $season++) {
            for ($round = 1; $round <= self::ROUND_COUNT; $round++) {
                $gameRound = new GameRound()
                    ->setSeason($season)
                    ->setNumber($round);
                $manager->persist($gameRound);
                $this->addReference('game_round_' . $season . '_' . $round, $gameRound);
            }
        }
        $manager->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\Team;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixture for loading games data.
 */
class GameFixtures extends Fixture implements DependentFixtureInterface
{
    use FakerTrait;

    /**
     * Load games data.
     */
    public function load(ObjectManager $manager): void
    {
        $faker = self::getFaker();
        $teams = $manager->getRepository(Team::class)->findAll();

        foreach ($teams as $team) {
            // Create a game for each team in each round of the first season
            for ($round = 1; $round <= GameRoundFixtures::ROUND_COUNT; ++$round) {
                $gameRound = $this->getReference('game_round_1_' . $round, GameRound::class);

                $game = new Game();
                $game->setTeam($team);
                $game->setRound($gameRound);

                // Randomly set some games as started or finished
                if ($faker->boolean(70)) { // 70% chance of being started
                    $startedAt = DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-3 months', '-1 week'));
                    $game->setStartedAt($startedAt);

                    // 50% chance of being finished if started
                    if ($faker->boolean(50)) {
                        $finishedAt = DateTimeImmutable::createFromMutable(
                            $faker->dateTimeBetween(
                                $startedAt->format('Y-m-d H:i:s'),
                                'now'
                            )
                        );
                        $game->setFinishedAt($finishedAt);
                    }
                }

                $manager->persist($game);
            }
        }

        $manager->flush();
    }

    /**
     * This fixture depends on TeamFixtures and GameRoundFixtures.
     *
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            TeamFixtures::class,
            GameRoundFixtures::class,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareRevealed;
use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use App\Entity\GameHighlight;
use App\Entity\GameRound;
use App\Entity\GameSquareAction;
use App\Entity\SquareActionType;
use App\Entity\Team;
use App\Service\GameTimerService;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

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

                $startedAt = null;
                $finishedAt = null;

                // Randomly set some games as started or finished
                if ($faker->boolean(70)) { // 70% chance of being started
                    $startedAt = DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-3 months', '-1 week'));
                    // The countdown of a game still in progress is up long ago.
                    $countdownZeroAt = $startedAt->modify('+' . GameTimerService::DEFAULT_DURATION_SECONDS . ' seconds');
                    $game->resetTimer($startedAt, $countdownZeroAt);

                    // 50% chance of being finished if started, at the latest when its time is up
                    if ($faker->boolean(50)) {
                        $finishedAt = DateTimeImmutable::createFromMutable(
                            $faker->dateTimeBetween(
                                $startedAt->format('Y-m-d H:i:s'),
                                $countdownZeroAt->format('Y-m-d H:i:s'),
                            )
                        );
                        $game->setFinishedAt($finishedAt);
                    }
                }

                $manager->persist($game);

                if ($startedAt !== null) {
                    $this->playSquares($manager, $faker, $game, $startedAt, $finishedAt ?? new DateTimeImmutable());
                    $this->markHighlights($manager, $faker, $game, $startedAt, $finishedAt ?? new DateTimeImmutable());
                }
            }
        }

        $manager->flush();
    }

    /**
     * Reveals and validates some squares of the game, with the dated history
     * of these actions, some of them repeated (revealed, concealed, then
     * revealed again).
     */
    private function playSquares(ObjectManager $manager, Generator $faker, Game $game, DateTimeImmutable $from, DateTimeImmutable $to): void
    {
        $squares = $manager->getRepository(BingoSquare::class)->findBy(['round' => $game->getRound()]);
        $hidden = array_values(array_filter($squares, static fn (BingoSquare $square): bool => !$square->isVisible()));
        $visible = array_values(array_filter($squares, static fn (BingoSquare $square): bool => $square->isVisible()));

        // Squares that can be validated, with the date from which they can be.
        $playable = array_map(static fn (BingoSquare $square): array => [$square, $from], $visible);
        foreach ($faker->randomElements($hidden, 6) as $square) {
            $revealedAt = $this->playActions($manager, $faker, $game, $square, $from, $to, SquareActionType::Reveal, SquareActionType::Conceal, new BingoSquareRevealed());
            if ($revealedAt !== null) {
                $playable[] = [$square, $revealedAt];
            }
        }

        foreach ($faker->randomElements($playable, 3) as [$square, $playableFrom]) {
            $this->playActions($manager, $faker, $game, $square, $playableFrom, $to, SquareActionType::Validate, SquareActionType::Invalidate, new BingoSquareValidated());
        }
    }

    /**
     * Marks up to five highlights during the game, some of them with a note.
     */
    private function markHighlights(ObjectManager $manager, Generator $faker, Game $game, DateTimeImmutable $from, DateTimeImmutable $to): void
    {
        for ($i = $faker->numberBetween(0, 5); $i > 0; --$i) {
            $markedAt = DateTimeImmutable::createFromMutable($faker->dateTimeBetween($from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')));
            $manager->persist(new GameHighlight($game, $markedAt)->setNote($faker->boolean() ? $faker->sentence() : null));
        }
    }

    /**
     * Records one to three alternating actions (mark, unmark, mark) on the
     * square and keeps the mark when the last action is a mark.
     *
     * @return DateTimeImmutable|null date of the mark the square keeps, null if it is left unmarked
     */
    private function playActions(
        ObjectManager $manager,
        Generator $faker,
        Game $game,
        BingoSquare $square,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        SquareActionType $markAction,
        SquareActionType $unmarkAction,
        BingoSquareRevealed|BingoSquareValidated $mark,
    ): ?DateTimeImmutable {
        $dates = [];
        for ($i = $faker->numberBetween(1, 3); $i > 0; --$i) {
            $dates[] = DateTimeImmutable::createFromMutable($faker->dateTimeBetween($from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')));
        }
        sort($dates);

        foreach ($dates as $i => $date) {
            $action = $i % 2 === 0 ? $markAction : $unmarkAction;
            $manager->persist(new GameSquareAction($game, $square, $action)->setOccurredAt($date));
        }

        if (count($dates) % 2 === 0) {
            return null;
        }

        $markedAt = end($dates);
        $mark instanceof BingoSquareRevealed ? $mark->setRevealedAt($markedAt) : $mark->setValidatedAt($markedAt);
        $manager->persist($mark->setGame($game)->setBingoSquare($square));

        return $markedAt;
    }

    /**
     * This fixture depends on TeamFixtures, GameRoundFixtures and BingoSquareFixtures.
     *
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            TeamFixtures::class,
            GameRoundFixtures::class,
            BingoSquareFixtures::class,
        ];
    }
}

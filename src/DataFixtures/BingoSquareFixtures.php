<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\GameRound;
use App\Service\BingoService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixture for loading bingo squares data.
 */
class BingoSquareFixtures extends Fixture implements DependentFixtureInterface
{
    use FakerTrait;

    /**
     * Load bingo squares data.
     */
    public function load(ObjectManager $manager): void
    {
        $faker = self::getFaker();
        $bingosCount = GameRoundFixtures::SEASON_COUNT * GameRoundFixtures::ROUND_COUNT;
        $squaresCount = BingoService::MAX_ROWS * BingoService::MAX_ROWS;

        for ($bingo = 1; $bingo <= $bingosCount; $bingo++) {
            $season = (int) ceil($bingo / GameRoundFixtures::ROUND_COUNT);
            $round = ($bingo - 1) % GameRoundFixtures::ROUND_COUNT + 1;

            for ($square = 1; $square <= $squaresCount; $square++) {
                $row = (int) ceil($square / BingoService::MAX_COLS);
                $col = ($square - 1) % BingoService::MAX_COLS + 1;

                $gameRound = $this->getReference('game_round_' . $season . '_' . $round, GameRound::class);
                $category = $this->getReference('category_' . $faker->numberBetween(0, 4), BingoSquareCategory::class);

                $bingoSquare = new BingoSquare()
                    ->setRound($gameRound)
                    ->setCategory($category)
                    ->setLabel($faker->text)
                    ->setRow($row)
                    ->setCol($col)
                    ->setVisible(BingoService::isSquareVisible($row, $col));
                $manager->persist($bingoSquare);
            }
        }

        $manager->flush();
    }

    /**
     * Get the dependencies of this fixture.
     *
     * @return array<class-string>
     */
    public function getDependencies(): array
    {
        return [
            GameRoundFixtures::class,
            BingoSquareCategoryFixtures::class,
        ];
    }
}

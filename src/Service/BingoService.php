<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\BingoDto;
use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\Rule;
use App\Repository\GameRoundRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @phpstan-import-type BingoSquareGrid from BingoDto
 */
readonly class BingoService
{
    private const array VISIBLE_SQUARES = [
        [1, 3],
        [2, 4],
        [3, 1],
        [3, 3],
        [3, 5],
        [4, 2],
        [5, 3],
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private GameRoundRepository $gameRoundRepository,
    ) {
    }

    public static function isSquareVisible(int $row, int $col): bool
    {
        return in_array([$row, $col], self::VISIBLE_SQUARES, true);
    }

    public function entityToDto(GameRound $gameRound): BingoDto
    {
        $squares = [];
        foreach ($gameRound->getBingoSquares() as $bingoSquare) {
            $data = [
                'id' => $bingoSquare->getId(),
                'category' => $bingoSquare->getCategory()?->getId(),
                'label' => $bingoSquare->getLabel(),
                'visible' => $bingoSquare->isVisible() ? 1 : 0,
            ];
            $squares[$bingoSquare->getRow()][$bingoSquare->getCol()] = $data;
        }

        return new BingoDto($gameRound->getSeason(), $gameRound->getNumber(), $squares);
    }

    // The round as edited in the referee form: its squares and its rules.
    public function entityToFormDto(GameRound $gameRound): BingoDto
    {
        $bingoDto = $this->entityToDto($gameRound);
        $bingoDto->rules = array_values($gameRound->getRules()->toArray());

        return $bingoDto;
    }

    /**
     * Squares of the game's round as a player sees them: until the game is
     * over, the label and category of every square that is neither visible nor
     * revealed are removed, before the start too, so they never reach the
     * browser. Referees get every square.
     *
     * @param array<int, int> $revealedSquareIds revealed square ids as keys (see GameSquareService::getRevealedSquareIds())
     *
     * @return BingoSquareGrid
     */
    public function gameSquares(Game $game, bool $isReferee, array $revealedSquareIds): array
    {
        $squares = $this->entityToDto($game->getRound())->squares;
        if ($isReferee || $game->isFinished()) {
            return $squares;
        }

        foreach ($squares as $row => $cols) {
            foreach ($cols as $col => $square) {
                if (self::isSquareVisible($row, $col) || isset($revealedSquareIds[$square['id']])) {
                    continue;
                }
                $squares[$row][$col]['label'] = null;
                $squares[$row][$col]['category'] = null;
            }
        }

        return $squares;
    }

    public function create(BingoDto $bingoDto): GameRound
    {
        $gameRound = new GameRound()
            ->setNumber($bingoDto->round)
            ->setSeason($bingoDto->season);
        $this->em->persist($gameRound);

        $this->fillCollection($bingoDto, $gameRound);
        $this->fillRules($bingoDto, $gameRound);
        $this->em->flush();

        return $gameRound;
    }

    public function update(BingoDto $bingoDto, GameRound $gameRound): void
    {
        $gameRound
            ->setNumber($bingoDto->round)
            ->setSeason($bingoDto->season);
        $this->em->persist($gameRound);

        $this->fillCollection($bingoDto, $gameRound);
        $this->fillRules($bingoDto, $gameRound);
        $this->em->flush();
    }

    public function createEmptyRound(int $season, int $round): GameRound
    {
        $gameRound = new GameRound()
            ->setNumber($round)
            ->setSeason($season);
        $this->em->persist($gameRound);
        $this->em->flush();

        return $gameRound;
    }

    public function fillSeasonGaps(int $season): void
    {
        $numbers = $this->gameRoundRepository->findNumbersForSeason($season);

        if ($numbers === []) {
            return;
        }

        $existing = array_flip($numbers);
        $max = max($numbers);

        for ($i = 1; $i < $max; ++$i) {
            if (!isset($existing[$i])) {
                $this->createEmptyRound($season, $i);
            }
        }
    }

    // A rule removed from the round loses its values in the games of the round.
    private function fillRules(BingoDto $bingoDto, GameRound $gameRound): void
    {
        foreach ($gameRound->getRules() as $rule) {
            if (!in_array($rule, $bingoDto->rules, true)) {
                $gameRound->removeRule($rule);
                $this->removeRuleValues($gameRound, $rule);
            }
        }
        foreach ($bingoDto->rules as $rule) {
            $gameRound->addRule($rule);
        }
    }

    private function removeRuleValues(GameRound $gameRound, Rule $rule): void
    {
        foreach ($gameRound->getGames() as $game) {
            foreach ($game->getRuleValues() as $ruleValue) {
                if ($ruleValue->getRule() === $rule) {
                    $game->getRuleValues()->removeElement($ruleValue);
                    $this->em->remove($ruleValue);
                }
            }
        }
    }

    private function fillCollection(BingoDto $bingoDto, GameRound $gameRound): void
    {
        foreach ($bingoDto->squares as $row => $cols) {
            foreach ($cols as $col => $data) {
                ['id' => $id, 'category' => $category, 'label' => $label, 'visible' => $visible] = $data + ['id' => null];
                if ($id !== null && $id !== '') {
                    $bingoSquare = $this->em->getReference(BingoSquare::class, $id);
                } else {
                    $bingoSquare = new BingoSquare()
                        ->setRound($gameRound)
                        ->setRow($row)
                        ->setCol($col);
                }
                $bingoSquare
                    ->setLabel($label ?? '')
                    ->setVisible($visible === '1');

                if ($category !== null && $category !== '') {
                    $bingoSquare->setCategory($this->em->getReference(BingoSquareCategory::class, $category));
                } else {
                    $bingoSquare->setCategory(null);
                }

                $this->em->persist($bingoSquare);
            }
        }
    }
}

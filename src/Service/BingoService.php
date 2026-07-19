<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\BingoDto;
use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\BingoSquareRevealed;
use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Repository\BingoSquareRevealedRepository;
use App\Repository\BingoSquareValidatedRepository;
use App\Repository\GameRoundRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

readonly class BingoService
{
    public const int MAX_ROWS = 5;
    public const int MAX_COLS = 5;

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
        private EntityManagerInterface         $em,
        private GameRoundRepository            $gameRoundRepository,
        private BingoSquareRevealedRepository  $bingoSquareRevealedRepository,
        private BingoSquareValidatedRepository $bingoSquareValidatedRepository,
        private GameService                    $gameService,
    )
    {
    }

    public static function isSquareVisible(int $row, int $col): bool
    {
        return in_array([$row, $col], self::VISIBLE_SQUARES, true);
    }

    public function getRevealedSquare(Game $game, int $squareId): ?BingoSquareRevealed
    {
        return $this->bingoSquareRevealedRepository->findOneBy(['game' => $game, 'bingoSquare' => $squareId]);
    }

    public function getValidatedSquare(Game $game, int $squareId): ?BingoSquareValidated
    {
        return $this->bingoSquareValidatedRepository->findOneBy(['game' => $game, 'bingoSquare' => $squareId]);
    }

    public function entityToDto(GameRound $gameRound): BingoDto
    {
        $squares = [];
        foreach ($gameRound->getBingoSquares() as $bingoSquare) {
            $data = [
                'id' => $bingoSquare->getId(),
                'category' => $bingoSquare->getCategory()->getId(),
                'label' => $bingoSquare->getLabel(),
                'visible' => $bingoSquare->isVisible() ? 1 : 0,
            ];
            $squares[$bingoSquare->getRow()][$bingoSquare->getCol()] = $data;
        }
        return new BingoDto($gameRound->getSeason(), $gameRound->getNumber(), $squares);
    }

    public function create(BingoDto $bingoDto): GameRound
    {
        $gameRound = new GameRound()
            ->setNumber($bingoDto->round)
            ->setSeason($bingoDto->season);
        $this->em->persist($gameRound);

        $this->fillCollection($bingoDto, $gameRound);
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

    public function findMaxRoundForSeason(int $season): int
    {
        $result = $this->gameRoundRepository->createQueryBuilder('gr')
            ->select('COALESCE(MAX(gr.number), 0)')
            ->where('gr.season = :season')
            ->setParameter('season', $season)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function findMaxSeason(): int
    {
        $result = $this->gameRoundRepository->createQueryBuilder('gr')
            ->select('COALESCE(MAX(gr.season), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function fillSeasonGaps(int $season): void
    {
        $numbers = $this->gameRoundRepository->createQueryBuilder('gr')
            ->select('gr.number')
            ->where('gr.season = :season')
            ->setParameter('season', $season)
            ->getQuery()
            ->getSingleColumnResult();

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
                    ->setLabel($label)
                    ->setVisible($visible === '1')
                    ->setCategory($this->em->getReference(BingoSquareCategory::class, $category));
                $this->em->persist($bingoSquare);
            }
        }
    }

    public function refreshBingoCard(Game $game): void
    {
        $this->gameService->refreshGameFrame($game, RefreshEvent::BingoCard);
        $this->gameService->refreshGameFrame($game, RefreshEvent::GameSteps);
    }

    public function reveal(Game $game, int $squareId): void
    {
        try {
            $square = new BingoSquareRevealed()
                ->setGame($game)
                ->setBingoSquare($this->em->getReference(BingoSquare::class, $squareId))
                ->setRevealedAt(new DateTimeImmutable());
            $this->em->persist($square);
            $this->em->flush();
            $this->refreshBingoCard($game);
        } catch (UniqueConstraintViolationException $e) {
        }
    }

    public function conceal(Game $game, int $squareId): void
    {
        try {
            $square = $this->getRevealedSquare($game, $squareId);
            if ($square !== null) {
                $this->em->remove($square);
                $this->em->flush();
            }
            $this->refreshBingoCard($game);
        } catch (UniqueConstraintViolationException $e) {
        }
    }

    public function validate(Game $game, int $squareId): void
    {
        try {
            $square = new BingoSquareValidated()
                ->setGame($game)
                ->setBingoSquare($this->em->getReference(BingoSquare::class, $squareId))
                ->setValidatedAt(new DateTimeImmutable());
            $this->em->persist($square);
            $this->em->flush();
            $this->refreshBingoCard($game);
        } catch (UniqueConstraintViolationException $e) {
        }
    }

    public function invalidate(Game $game, int $squareId): void
    {
        try {
            $square = $this->getValidatedSquare($game, $squareId);
            if ($square !== null) {
                $this->em->remove($square);
                $this->em->flush();
            }
            $this->refreshBingoCard($game);
        } catch (UniqueConstraintViolationException $e) {
        }
    }
}

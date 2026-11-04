<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\GameRound;
use App\Repository\GameRoundRepository;
use App\Service\BingoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BingoServiceTest extends TestCase
{
    /**
     * Builds the service, replacing every collaborator not given by a stub.
     */
    private function createBingoService(?EntityManagerInterface $em = null): BingoService
    {
        return new BingoService(
            $em ?? $this->createStub(EntityManagerInterface::class),
            $this->createStub(GameRoundRepository::class),
        );
    }

    #[DataProvider('visibleSquaresProvider')]
    public function testIsSquareVisibleReturnsTrueForCrossPattern(int $row, int $col): void
    {
        $this->assertTrue(BingoService::isSquareVisible($row, $col));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function visibleSquaresProvider(): array
    {
        return [
            'row 1 col 3' => [1, 3],
            'row 2 col 4' => [2, 4],
            'row 3 col 1' => [3, 1],
            'row 3 col 3' => [3, 3],
            'row 3 col 5' => [3, 5],
            'row 4 col 2' => [4, 2],
            'row 5 col 3' => [5, 3],
        ];
    }

    #[DataProvider('hiddenSquaresProvider')]
    public function testIsSquareVisibleReturnsFalseForNonCrossPattern(int $row, int $col): void
    {
        $this->assertFalse(BingoService::isSquareVisible($row, $col));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function hiddenSquaresProvider(): array
    {
        return [
            'row 1 col 1' => [1, 1],
            'row 1 col 2' => [1, 2],
            'row 2 col 1' => [2, 1],
            'row 2 col 2' => [2, 2],
            'row 4 col 1' => [4, 1],
            'row 5 col 1' => [5, 1],
            'row 5 col 5' => [5, 5],
        ];
    }

    public function testEntityToDtoConvertsRoundToDto(): void
    {
        $round = new GameRound();
        $round->setSeason(2)->setNumber(3);

        $category = $this->createConfiguredStub(BingoSquareCategory::class, [
            'getId' => 1,
        ]);

        $square = new BingoSquare();
        $square->setRow(1)->setCol(1)->setLabel('Test')->setVisible(true)->setCategory($category);
        $round->addBingoSquare($square);

        $dto = $this->createBingoService()->entityToDto($round);

        $this->assertSame(2, $dto->season);
        $this->assertSame(3, $dto->round);
        $this->assertArrayHasKey(1, $dto->squares);
        $this->assertArrayHasKey(1, $dto->squares[1]);
        $this->assertNull($dto->squares[1][1]['id']);
        $this->assertSame(1, $dto->squares[1][1]['category']);
        $this->assertSame('Test', $dto->squares[1][1]['label']);
        $this->assertSame(1, $dto->squares[1][1]['visible']);
    }

    public function testEntityToDtoEncodesInvisibleAsZero(): void
    {
        $round = new GameRound();
        $round->setSeason(1)->setNumber(1);

        $category = $this->createConfiguredStub(BingoSquareCategory::class, [
            'getId' => 1,
        ]);

        $square = new BingoSquare();
        $square->setRow(2)->setCol(3)->setLabel('Hidden')->setVisible(false)->setCategory($category);
        $round->addBingoSquare($square);

        $dto = $this->createBingoService()->entityToDto($round);

        $this->assertSame(0, $dto->squares[2][3]['visible']);
    }

    public function testEntityToDtoReturnsEmptySquaresForEmptyRound(): void
    {
        $round = new GameRound();
        $round->setSeason(1)->setNumber(1);

        $dto = $this->createBingoService()->entityToDto($round);

        $this->assertSame([], $dto->squares);
    }

    public function testCreateEmptyRoundPersistsAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(GameRound::class));
        $em->expects($this->once())->method('flush');

        $result = $this->createBingoService(em: $em)->createEmptyRound(2, 5);

        $this->assertSame(2, $result->getSeason());
        $this->assertSame(5, $result->getNumber());
    }
}

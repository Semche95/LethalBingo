<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\BingoSquare;
use App\Entity\GameRound;
use PHPUnit\Framework\TestCase;

class GameRoundTest extends TestCase
{
    public function testIsCompleteReturnsFalseWhenNoSquares(): void
    {
        $round = new GameRound();

        $this->assertFalse($round->isComplete());
    }

    public function testIsCompleteReturnsFalseWhenLessThan25Squares(): void
    {
        $round = new GameRound();
        for ($i = 0; $i < 24; ++$i) {
            $square = new BingoSquare();
            $square->setRow(intdiv($i, 5) + 1)->setCol(($i % 5) + 1);
            $round->addBingoSquare($square);
        }

        $this->assertFalse($round->isComplete());
    }

    public function testIsCompleteReturnsTrueWithExactly25Squares(): void
    {
        $round = new GameRound();
        for ($row = 1; $row <= 5; ++$row) {
            for ($col = 1; $col <= 5; ++$col) {
                $square = new BingoSquare();
                $square->setRow($row)->setCol($col);
                $round->addBingoSquare($square);
            }
        }

        $this->assertTrue($round->isComplete());
    }

    public function testIsCompleteReturnsFalseWithMoreThan25Squares(): void
    {
        $round = new GameRound();
        for ($row = 1; $row <= 5; ++$row) {
            for ($col = 1; $col <= 5; ++$col) {
                $square = new BingoSquare();
                $square->setRow($row)->setCol($col);
                $round->addBingoSquare($square);
            }
        }
        $extra = new BingoSquare();
        $extra->setRow(1)->setCol(1);
        $round->addBingoSquare($extra);

        $this->assertFalse($round->isComplete());
    }

    public function testGetMissingSquaresReturnsAllCoordsWhenEmpty(): void
    {
        $round = new GameRound();

        $missing = $round->getMissingSquares();

        $this->assertCount(25, $missing);
        $this->assertContains('A1', $missing);
        $this->assertContains('E5', $missing);
        $this->assertContains('C3', $missing);
    }

    public function testGetMissingSquaresReturnsEmptyArrayWhenComplete(): void
    {
        $round = new GameRound();
        for ($row = 1; $row <= 5; ++$row) {
            for ($col = 1; $col <= 5; ++$col) {
                $square = new BingoSquare();
                $square->setRow($row)->setCol($col);
                $round->addBingoSquare($square);
            }
        }

        $missing = $round->getMissingSquares();

        $this->assertCount(0, $missing);
    }

    public function testGetMissingSquaresReturnsCorrectCoordinates(): void
    {
        $round = new GameRound();

        $square = new BingoSquare();
        $square->setRow(1)->setCol(1);
        $round->addBingoSquare($square);

        $square = new BingoSquare();
        $square->setRow(3)->setCol(5);
        $round->addBingoSquare($square);

        $missing = $round->getMissingSquares();

        $this->assertNotContains('A1', $missing);
        $this->assertNotContains('C5', $missing);
        $this->assertCount(23, $missing);
    }

    public function testToString(): void
    {
        $round = new GameRound();
        $round->setNumber(3)->setSeason(2);

        $this->assertSame('Round 3 Saison 2', (string) $round);
    }

    public function testToStringReturnsEmptyStringWhenIncomplete(): void
    {
        $round = new GameRound();

        $this->assertSame('', (string) $round);
    }

    public function testDefaultSeasonIsOne(): void
    {
        $round = new GameRound();

        $this->assertSame(1, $round->getSeason());
    }

    public function testSetNumberAndGetNumber(): void
    {
        $round = new GameRound();
        $round->setNumber(42);

        $this->assertSame(42, $round->getNumber());
    }

    public function testSetSeasonAndGetSeason(): void
    {
        $round = new GameRound();
        $round->setSeason(5);

        $this->assertSame(5, $round->getSeason());
    }

    public function testAddBingoSquareSetsRound(): void
    {
        $round = new GameRound();
        $square = new BingoSquare();
        $square->setRow(1)->setCol(1);

        $round->addBingoSquare($square);

        $this->assertSame($round, $square->getRound());
        $this->assertCount(1, $round->getBingoSquares());
    }

    public function testAddBingoSquareDoesNotDuplicate(): void
    {
        $round = new GameRound();
        $square = new BingoSquare();
        $square->setRow(1)->setCol(1);

        $round->addBingoSquare($square);
        $round->addBingoSquare($square);

        $this->assertCount(1, $round->getBingoSquares());
    }

    public function testRemoveBingoSquareRemovesItFromTheRound(): void
    {
        $round = new GameRound();
        $square = new BingoSquare();
        $square->setRow(1)->setCol(1);

        $round->addBingoSquare($square);
        $round->removeBingoSquare($square);

        $this->assertCount(0, $round->getBingoSquares());
        $this->assertFalse($round->getBingoSquares()->contains($square));
    }

    public function testGetMissingSquaresFormatFollowsRowLetterColPattern(): void
    {
        $round = new GameRound();

        $missing = $round->getMissingSquares();

        foreach ($missing as $coord) {
            $this->assertMatchesRegularExpression('/^[A-E][1-5]$/', $coord);
        }
    }
}

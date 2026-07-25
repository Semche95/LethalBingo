<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\GameRound;
use PHPUnit\Framework\TestCase;

class BingoSquareCategoryTest extends TestCase
{
    public function testGetLabelAndSetLabel(): void
    {
        $category = new BingoSquareCategory();
        $category->setLabel('Category A');

        $this->assertSame('Category A', $category->getLabel());
    }

    public function testGetPositionAndSetPosition(): void
    {
        $category = new BingoSquareCategory();
        $category->setPosition(3);

        $this->assertSame(3, $category->getPosition());
    }

    public function testToStringReturnsLabel(): void
    {
        $category = new BingoSquareCategory();
        $category->setLabel('Test Category');

        $this->assertSame('Test Category', (string) $category);
    }

    public function testToStringReturnsEmptyStringWhenLabelIsNull(): void
    {
        $category = new BingoSquareCategory();

        $this->assertSame('', (string) $category);
    }

    public function testGetChildSquaresReturnsMatchingSquares(): void
    {
        $category = new BingoSquareCategory();
        $category->setLabel('Cat');

        $round1 = new GameRound();
        $round1->setNumber(1)->setSeason(1);
        $round2 = new GameRound();
        $round2->setNumber(2)->setSeason(1);

        $square1 = new BingoSquare();
        $square1->setRow(1)->setCol(1)->setRound($round1);
        $category->addBingoSquare($square1);

        $square2 = new BingoSquare();
        $square2->setRow(1)->setCol(2)->setRound($round2);
        $category->addBingoSquare($square2);

        $square3 = new BingoSquare();
        $square3->setRow(2)->setCol(1)->setRound($round1);
        $category->addBingoSquare($square3);

        $result = $category->getChildSquares($round1);

        $this->assertCount(2, $result);
    }

    public function testGetChildSquaresReturnsEmptyWhenNoMatch(): void
    {
        $category = new BingoSquareCategory();
        $category->setLabel('Cat');

        $round1 = new GameRound();
        $round1->setNumber(1)->setSeason(1);
        $round2 = new GameRound();
        $round2->setNumber(2)->setSeason(1);

        $square = new BingoSquare();
        $square->setRow(1)->setCol(1)->setRound($round1);
        $category->addBingoSquare($square);

        $result = $category->getChildSquares($round2);

        $this->assertCount(0, $result);
    }

    public function testAddBingoSquareSetsCategory(): void
    {
        $category = new BingoSquareCategory();
        $category->setLabel('Cat');
        $square = new BingoSquare();
        $square->setRow(1)->setCol(1);

        $category->addBingoSquare($square);

        $this->assertSame($category, $square->getCategory());
        $this->assertCount(1, $category->getBingoSquares());
    }

    public function testAddBingoSquareDoesNotDuplicate(): void
    {
        $category = new BingoSquareCategory();
        $category->setLabel('Cat');
        $square = new BingoSquare();
        $square->setRow(1)->setCol(1);

        $category->addBingoSquare($square);
        $category->addBingoSquare($square);

        $this->assertCount(1, $category->getBingoSquares());
    }

    public function testRemoveBingoSquare(): void
    {
        $category = new BingoSquareCategory();
        $category->setLabel('Cat');
        $square = new BingoSquare();
        $square->setRow(1)->setCol(1);

        $category->addBingoSquare($square);
        $category->removeBingoSquare($square);

        $this->assertCount(0, $category->getBingoSquares());
    }
}

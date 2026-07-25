<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\BingoSquare;
use PHPUnit\Framework\TestCase;

class BingoSquareTest extends TestCase
{
    /**
     * @dataProvider rowLetterProvider
     */
    public function testGetRowLetter(int $row, string $expected): void
    {
        $this->assertSame($expected, BingoSquare::getRowLetter($row));
    }

    public static function rowLetterProvider(): array
    {
        return [
            'row 1 is A' => [1, 'A'],
            'row 2 is B' => [2, 'B'],
            'row 3 is C' => [3, 'C'],
            'row 4 is D' => [4, 'D'],
            'row 5 is E' => [5, 'E'],
        ];
    }

    public function testGetCoordsReturnsLetterAndCol(): void
    {
        $square = new BingoSquare();
        $square->setRow(3)->setCol(2);

        $this->assertSame('C2', $square->getCoords());
    }

    public function testGetCoordsReturnsEmptyStringWhenRowIsNull(): void
    {
        $square = new BingoSquare();
        $square->setCol(2);

        $this->assertSame('', $square->getCoords());
    }

    public function testGetCoordsReturnsEmptyStringWhenColIsNull(): void
    {
        $square = new BingoSquare();
        $square->setRow(2);

        $this->assertSame('', $square->getCoords());
    }

    public function testGetCoordsReturnsEmptyStringWhenBothAreNull(): void
    {
        $square = new BingoSquare();

        $this->assertSame('', $square->getCoords());
    }

    public function testToStringReturnsLabel(): void
    {
        $square = new BingoSquare();
        $square->setLabel('My Square');

        $this->assertSame('My Square', (string) $square);
    }

    public function testToStringReturnsEmptyStringWhenLabelIsNull(): void
    {
        $square = new BingoSquare();

        $this->assertSame('', (string) $square);
    }

    public function testGettersAndSetters(): void
    {
        $square = new BingoSquare();

        $square->setLabel('Test');
        $this->assertSame('Test', $square->getLabel());

        $square->setRow(3);
        $this->assertSame(3, $square->getRow());

        $square->setCol(4);
        $this->assertSame(4, $square->getCol());

        $square->setVisible(true);
        $this->assertTrue($square->isVisible());

        $square->setVisible(false);
        $this->assertFalse($square->isVisible());
    }
}

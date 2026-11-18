<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Measures the application computes from the grid of a game: their rules are
 * predefined, the referee only changes their coefficient.
 */
enum ComputedMeasure: string
{
    case ValidatedSquares = 'validated_squares';
    case FourSquareLines = 'four_square_lines';
    case FiveSquareLines = 'five_square_lines';

    public function label(): string
    {
        return match ($this) {
            self::ValidatedSquares => 'Cases validées',
            self::FourSquareLines => 'Lignes de 4 cases validées',
            self::FiveSquareLines => 'Lignes de 5 cases validées',
        };
    }
}

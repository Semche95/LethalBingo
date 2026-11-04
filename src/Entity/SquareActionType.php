<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Action performed on a square during a game.
 */
enum SquareActionType: string
{
    case Reveal = 'reveal';
    case Conceal = 'conceal';
    case Validate = 'validate';
    case Invalidate = 'invalidate';
}

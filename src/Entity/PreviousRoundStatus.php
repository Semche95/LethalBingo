<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Why the team of a game has not finished its game of the previous round,
 * shown to the referee before the start (docs/GAME.md section 2).
 */
enum PreviousRoundStatus: string
{
    case NoGame = 'no_game';
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
}

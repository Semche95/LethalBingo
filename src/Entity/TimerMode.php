<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * How the timer of a game counts: down from a chosen duration, or up from the
 * start when the game has no time limit (docs/TIMER.md section 8).
 */
enum TimerMode: string
{
    case Countdown = 'countdown';
    case Stopwatch = 'stopwatch';
}

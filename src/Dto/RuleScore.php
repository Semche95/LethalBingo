<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Rule;

/**
 * Points earned by a rule in a game: its value times its coefficient.
 */
final readonly class RuleScore
{
    public function __construct(
        public Rule $rule,
        public int $value,
        public int $points,
    ) {
    }
}

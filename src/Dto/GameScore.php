<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Points of a game, rule by rule, and their total.
 */
final readonly class GameScore
{
    /**
     * @param list<RuleScore> $rules in the order of the rules of the round
     */
    public function __construct(
        public array $rules,
        public int $total,
    ) {
    }
}

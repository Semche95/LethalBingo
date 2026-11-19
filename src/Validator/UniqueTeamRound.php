<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * A team plays each round only once: no other game may exist for the team
 * and round of the validated GameDto.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class UniqueTeamRound extends Constraint
{
    public string $message = 'Cette équipe a déjà une partie sur ce bingo.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

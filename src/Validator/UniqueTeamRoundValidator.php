<?php

declare(strict_types=1);

namespace App\Validator;

use App\Dto\GameDto;
use App\Entity\Game;
use App\Repository\GameRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueTeamRoundValidator extends ConstraintValidator
{
    public function __construct(private readonly GameRepository $gameRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueTeamRound) {
            throw new UnexpectedTypeException($constraint, UniqueTeamRound::class);
        }

        if (!$value instanceof GameDto || $value->team === null || $value->round === null) {
            return;
        }

        $games = $this->gameRepository->findBy(['team' => $value->team, 'round' => $value->round]);
        $otherGames = array_filter($games, fn (Game $game): bool => $game !== $value->game);
        if ($otherGames === []) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath('round')
            ->addViolation();
    }
}

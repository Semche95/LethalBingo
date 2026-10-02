<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\GameRound;
use App\Entity\Team;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A game as edited in the referee form: team and round are null until chosen.
 */
class GameDto
{
    public function __construct(
        #[Assert\NotNull(message: 'Veuillez sélectionner une équipe.')]
        public ?Team $team = null,
        #[Assert\NotNull(message: 'Veuillez sélectionner un bingo.')]
        public ?GameRound $round = null,
    ) {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        if ($this->round !== null && !$this->round->isComplete()) {
            $context->buildViolation('Le bingo sélectionné est incomplet et ne peut pas être utilisé pour une partie.')
                ->atPath('round')
                ->addViolation();
        }
    }
}

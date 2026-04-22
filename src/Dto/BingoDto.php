<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BingoDto
{
    public function __construct(
        #[Assert\Positive]
        public ?int $season = null,
        #[Assert\Positive]
        public ?int $round = null,
        public array $squares = [],
    )
    {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        $labels = [];
        foreach ($this->squares as $square) {
            array_push($labels, ...array_column($square, 'label'));
        }
        if (count(array_unique($labels)) !== count($labels)) {
            $context->buildViolation('Des labels sont dupliqués dans le bingo.')
                ->atPath('squares')
                ->addViolation();
        }
    }
}

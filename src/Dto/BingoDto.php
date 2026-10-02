<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BingoDto
{
    public array $duplicateCoords = [];

    public function __construct(
        #[Assert\Positive]
        public ?int $season = null,
        #[Assert\Positive]
        public ?int $round = null,
        public array $squares = [],
    ) {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        $seen = [];
        $duplicates = [];
        foreach ($this->squares as $row => $cols) {
            foreach ($cols as $col => $data) {
                $label = $data['label'] ?? '';
                if (is_string($label) && trim($label) !== '') {
                    $normalized = trim($label);
                    $coord = $row . '-' . $col;
                    if (isset($seen[$normalized])) {
                        $duplicates[$coord] = true;
                        $duplicates[$seen[$normalized]] = true;
                    } else {
                        $seen[$normalized] = $coord;
                    }
                }
            }
        }
        if ($duplicates !== []) {
            $this->duplicateCoords = array_keys($duplicates);
            $context->buildViolation('Des labels sont dupliqués dans le bingo.')
                ->atPath('squares')
                ->addViolation();
        }
    }
}

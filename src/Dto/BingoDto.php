<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A bingo square as edited in the form or displayed in the card: ids and
 * visibility come as strings from a form submission, as integers from the
 * database; label and category are null when hidden from a player.
 *
 * @phpstan-type BingoSquareData array{id?: int|string|null, category: int|string|null, label: string|null, visible: int|string}
 * @phpstan-type BingoSquareGrid array<int, array<int, BingoSquareData>>
 */
class BingoDto
{
    /** @var list<string> coordinates ("row-col") of the squares sharing a label */
    public array $duplicateCoords = [];

    /**
     * @param BingoSquareGrid $squares squares indexed by row then column
     */
    public function __construct(
        #[Assert\NotNull(message: 'Veuillez saisir une saison.')]
        #[Assert\Positive]
        public ?int $season = null,
        #[Assert\NotNull(message: 'Veuillez saisir un numéro de round.')]
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
                $normalized = trim($data['label'] ?? '');
                if ($normalized !== '') {
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

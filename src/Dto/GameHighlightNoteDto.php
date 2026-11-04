<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Note of a highlight, saved while the referee types it.
 */
final readonly class GameHighlightNoteDto
{
    public function __construct(
        public string $note = '',
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Twig;

use IntlDateFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class JsonExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', static fn (string $json): array => json_decode($json, true, 512, \JSON_THROW_ON_ERROR)),
            new TwigFilter('format_game_date', static function (?string $dateString): string {
                if ($dateString === null || $dateString === '') {
                    return '';
                }

                $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateString);

                if ($date === false) {
                    return $dateString;
                }

                return (new IntlDateFormatter(
                    'fr_FR',
                    IntlDateFormatter::NONE,
                    IntlDateFormatter::NONE,
                    null,
                    IntlDateFormatter::GREGORIAN,
                    'd MMM H:mm',
                ))->format($date);
            }),
        ];
    }
}

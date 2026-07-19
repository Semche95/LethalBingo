<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class JsonExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', static fn (string $json): array => json_decode($json, true, 512, \JSON_THROW_ON_ERROR)),
        ];
    }
}

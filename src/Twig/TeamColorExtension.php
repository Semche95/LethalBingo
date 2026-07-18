<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TeamColorExtension extends AbstractExtension
{
    private const TEAM_COLORS = ['#378ADD', '#639922', '#7F77DD', '#D85A30', '#D4537E'];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('team_color', [$this, 'getTeamColor']),
        ];
    }

    public function getTeamColor(int $teamId): string
    {
        return self::TEAM_COLORS[$teamId % count(self::TEAM_COLORS)];
    }
}

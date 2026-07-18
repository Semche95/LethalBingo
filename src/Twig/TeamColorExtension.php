<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TeamColorExtension extends AbstractExtension
{
    private const TEAM_COLORS = ['#C57B57', '#C4A24A', '#7A9470', '#5A9088', '#8B6B8F', '#9C6B4F'];

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

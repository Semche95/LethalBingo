<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TeamColorExtension extends AbstractExtension
{
    private const array TEAM_COLORS = ['#6F92B8', '#D99B5C', '#C97066', '#6FADA6', '#7CA86B', '#D4BA6A', '#9A7FA8', '#CC8FA0', '#A8846C', '#98A0A8'];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('team_color', $this->getTeamColor(...)),
        ];
    }

    public function getTeamColor(int $teamId): string
    {
        return self::TEAM_COLORS[$teamId % count(self::TEAM_COLORS)];
    }
}

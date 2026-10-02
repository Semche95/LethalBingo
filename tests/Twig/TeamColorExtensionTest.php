<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\TeamColorExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TeamColorExtensionTest extends TestCase
{
    private TeamColorExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new TeamColorExtension();
    }

    public function testGetFunctionsReturnsOneFunction(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('team_color', $functions[0]->getName());
    }

    #[DataProvider('teamIdProvider')]
    public function testGetTeamColorReturnsValidHexColor(int $teamId): void
    {
        $color = $this->extension->getTeamColor($teamId);

        $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $color);
    }

    public static function teamIdProvider(): array
    {
        return [
            'team 0' => [0],
            'team 1' => [1],
            'team 5' => [5],
            'team 6' => [6],
            'team 12' => [12],
        ];
    }

    public function testGetTeamColorCyclesThroughPalette(): void
    {
        $color0 = $this->extension->getTeamColor(0);
        $color10 = $this->extension->getTeamColor(10);

        $this->assertSame($color0, $color10, 'Colors should cycle (modulo 10)');
    }

    public function testGetTeamColorReturnsDifferentColorsForConsecutiveIds(): void
    {
        $colors = [];
        for ($i = 0; $i < 10; ++$i) {
            $colors[] = $this->extension->getTeamColor($i);
        }

        $this->assertCount(10, array_unique($colors), 'All 10 palette colors should be unique');
    }
}

<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Repository\GameRoundRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
#[IsGranted('ROLE_REFEREE')]
final class BingoList
{
    use DefaultActionTrait;

    #[LiveProp]
    public array $bingosBySeason = [];

    /** @var array<int, list<int>> season => duplicated round numbers */
    #[LiveProp]
    public array $duplicatesBySeason = [];

    /** @var array<int, list<int>> season => missing round numbers */
    #[LiveProp]
    public array $gapsBySeason = [];

    /** @var array<int, string> season => human-readable gap label */
    #[LiveProp]
    public array $gapLabelsBySeason = [];

    public function __construct(
        private readonly GameRoundRepository $gameRoundRepository,
    )
    {
    }

    public function mount(): void
    {
        $this->bingosBySeason = $this->gameRoundRepository->findGroupedBySeason();
        $this->computeWarnings();
    }

    private function computeWarnings(): void
    {
        foreach ($this->bingosBySeason as $row) {
            $season = $row['season'];
            $rounds = json_decode($row['rounds'], true);
            $numbers = array_column($rounds, 'number');

            $counts = array_count_values($numbers);
            $this->duplicatesBySeason[$season] = array_values(array_unique(array_filter(
                $numbers,
                static fn(int $n): bool => $counts[$n] > 1,
            )));

            $max = max($numbers);
            $existing = array_unique($numbers);
            $missing = array_diff(range(1, $max), $existing);
            $this->gapsBySeason[$season] = array_values($missing);

            if ($missing !== []) {
                $this->gapLabelsBySeason[$season] = $this->buildGapLabel($missing);
            }
        }
    }

    /** @param list<int> $missing */
    private function buildGapLabel(array $missing): string
    {
        $count = count($missing);
        $numbers = implode(', ', $missing);

        if ($count === 1) {
            return 'Le round ' . $numbers . ' est manquant';
        }

        return 'Les rounds ' . $numbers . ' sont manquants';
    }
}

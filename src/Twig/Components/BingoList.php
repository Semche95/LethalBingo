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

    /** @var array<int, array{square_count: int, incomplete_content: bool}> roundId => completeness info */
    #[LiveProp]
    public array $completenessIndex = [];

    public function __construct(
        private readonly GameRoundRepository $gameRoundRepository,
    )
    {
    }

    public function mount(): void
    {
        $this->bingosBySeason = $this->gameRoundRepository->findGroupedBySeason();

        $completenessData = $this->gameRoundRepository->findSquareCompleteness();
        foreach ($completenessData as $row) {
            $this->completenessIndex[$row['round_id']] = [
                'square_count' => (int) $row['square_count'],
                'incomplete_content' => (int) $row['incomplete_count'] > 0,
            ];
        }
    }
}

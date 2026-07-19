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

    public function __construct(
        private readonly GameRoundRepository $gameRoundRepository,
    )
    {
    }

    public function mount(): void
    {
        $this->bingosBySeason = $this->gameRoundRepository->findGroupedBySeason();
    }
}

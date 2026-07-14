<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Repository\GameRoundRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent(defaultAction: 'mount')]
#[IsGranted('ROLE_REFEREE')]
final class Filter
{
    #[LiveProp(writable: true)]
    public ?int $round = null;

    #[LiveProp(writable: true)]
    public ?int $season = null;

    #[LiveProp]
    public array $rounds = [];

    #[LiveProp]
    public array $seasons = [];

    public function __construct(
        private readonly GameRoundRepository $gameRoundRepository,
    )
    {
    }

    public function mount(): void
    {
        $this->rounds = $this->gameRoundRepository->findDistinctRoundNumbers();
        $this->seasons = $this->gameRoundRepository->findDistinctSeasons();
    }
}

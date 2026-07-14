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

    #[LiveProp(writable: true, onUpdated: 'loadBingos')]
    public ?int $round = null;

    #[LiveProp(writable: true, onUpdated: 'loadBingos')]
    public ?int $season = null;

    #[LiveProp]
    public array $bingos = [];

    public function __construct(
        private readonly GameRoundRepository $gameRoundRepository,
    )
    {
    }

    public function mount(): void
    {
        $this->loadBingos();
    }

    public function loadBingos(): void
    {
        $this->bingos = $this->gameRoundRepository->findByRoundAndSeason($this->round, $this->season);
    }
}

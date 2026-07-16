<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Repository\GameRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
#[IsGranted('ROLE_REFEREE')]
final class GameList
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, onUpdated: 'loadGames')]
    public ?int $round = null;

    #[LiveProp(writable: true, onUpdated: 'loadGames')]
    public ?int $season = null;

    #[LiveProp(writable: true, onUpdated: 'loadGames')]
    public ?int $team = null;

    #[LiveProp(writable: true, onUpdated: 'loadGames')]
    public ?bool $started = null;

    #[LiveProp(writable: true, onUpdated: 'loadGames')]
    public ?bool $ended = null;

    #[LiveProp]
    public array $games = [];

    public function __construct(
        private readonly GameRepository $gameRepository,
    )
    {
    }

    public function mount(): void
    {
        $this->loadGames();
    }

    public function loadGames(): void
    {
        $this->games = $this->gameRepository->findByFilters(
            $this->round,
            $this->season,
            $this->team,
            $this->started,
            $this->ended
        );
    }
}

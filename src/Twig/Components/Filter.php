<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Repository\GameRoundRepository;
use App\Repository\TeamRepository;
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

    #[LiveProp(writable: true)]
    public ?int $team = null;

    #[LiveProp(writable: true, onUpdated: 'updateStarted')]
    public ?bool $started = null;

    #[LiveProp(writable: true, onUpdated: 'updateEnded')]
    public ?bool $ended = null;

    #[LiveProp]
    public array $rounds = [];

    #[LiveProp]
    public array $seasons = [];

    #[LiveProp]
    public array $teams = [];

    #[LiveProp]
    public string $context = 'default';

    public function __construct(
        private readonly GameRoundRepository $gameRoundRepository,
        private readonly TeamRepository $teamRepository,
    )
    {
    }

    public function mount(): void
    {
        $this->rounds = $this->gameRoundRepository->findDistinctRoundNumbers();
        $this->seasons = $this->gameRoundRepository->findDistinctSeasons();
        $this->teams = $this->teamRepository->findAllWithMembers();
    }

    public function updateStarted(string $value): void
    {
        $this->started = $this->convertStringToBool($value);
    }

    public function updateEnded(string $value): void
    {
        $this->ended = $this->convertStringToBool($value);
    }

    private function convertStringToBool(string $value): ?bool
    {
        return match($value) {
            'true' => true,
            'false' => false,
            'null', '' => null,
            default => null,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Repository\GameRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
#[IsGranted('ROLE_REFEREE')]
final class GameList
{
    use DefaultActionTrait;

    private const COOKIE_NAME = 'referee_game_seasons_open';

    #[LiveProp]
    public array $games = [];

    /** @var list<string> */
    public array $seasonsOpen = [];

    public function __construct(
        private readonly GameRepository $gameRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function mount(): void
    {
        $this->games = $this->gameRepository->findGamesForGrid();
        $this->seasonsOpen = $this->resolveOpenSeasons();
    }

    /** @return list<string> */
    private function resolveOpenSeasons(): array
    {
        $allSeasons = array_values(array_unique(array_column($this->games, 'season')));
        sort($allSeasons);

        if ($allSeasons === []) {
            return [];
        }

        $cookie = $this->requestStack->getCurrentRequest()?->cookies->get(self::COOKIE_NAME);

        if ($cookie === null) {
            return [(string) max($allSeasons)];
        }

        $stored = array_values(array_filter(explode(',', $cookie)));

        return array_values(array_intersect($stored, array_map('strval', $allSeasons)));
    }
}

<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Repository\TeamRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
#[IsGranted('ROLE_REFEREE')]
final class TeamList
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, onUpdated: 'loadTeams')]
    public string $search = '';

    /** @var list<array{id: int, name: string, members: string}> */
    #[LiveProp]
    public array $teams = [];

    public function __construct(
        private readonly TeamRepository $teamRepository,
    )
    {
    }

    public function mount(): void
    {
        $this->loadTeams();
    }

    public function loadTeams(): void
    {
        $this->teams = $this->teamRepository->findWithMembers($this->search !== '' ? $this->search : null);
    }
}

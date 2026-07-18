<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Team;
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

    /** @var Team[] */
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
        $this->teams = $this->teamRepository->findBy([], ['name' => 'ASC']);
    }
}

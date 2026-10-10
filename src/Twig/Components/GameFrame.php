<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Game;
use App\Service\GameService;
use App\Service\RefreshEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

#[AsLiveComponent]
final class GameFrame extends AbstractController
{
    use ComponentToolsTrait;

    #[LiveProp]
    public Game $game;

    public function __construct(
        private readonly GameService $gameService,
    ) {
    }

    #[LiveListener(RefreshEvent::GameFrame->value)]
    public function __invoke(): void
    {
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function startGame(): void
    {
        $this->gameService->start($this->game);
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function stopGame(): void
    {
        $this->gameService->stop($this->game);
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function resetGame(): void
    {
        $this->gameService->reset($this->game);
    }
}

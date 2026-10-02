<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Game;
use App\Service\GameService;
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

    #[LiveListener('gameFrameRefresh')]
    public function __invoke(): void
    {
    }

    public function isGameStarted(): bool
    {
        return $this->game->getStartedAt() !== null;
    }

    public function isGameFinished(): bool
    {
        return $this->game->getFinishedAt() !== null;
    }

    public function isInProgress(): bool
    {
        return $this->isGameStarted() && !$this->isGameFinished();
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

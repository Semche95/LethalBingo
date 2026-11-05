<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Game;
use App\Entity\GameSquareAction;
use App\Repository\GameSquareActionRepository;
use App\Service\RefreshEvent;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Referee only: history of the square actions and of the highlights of the
 * game, refreshed live and still readable once the game is over.
 */
#[AsLiveComponent]
final class GameHistory
{
    #[LiveProp]
    public Game $game;

    /** @var list<GameSquareAction>|null loaded once per render */
    private ?array $squareActions = null;

    public function __construct(
        private readonly GameSquareActionRepository $squareActionRepository,
    ) {
    }

    #[LiveListener(RefreshEvent::GameFrame->value)]
    #[LiveListener(RefreshEvent::BingoCard->value)]
    #[LiveListener(RefreshEvent::GameHistory->value)]
    // Also the default action: no re-render of the history outside a referee session.
    #[IsGranted('ROLE_REFEREE')]
    public function __invoke(): void
    {
    }

    /**
     * @return list<GameSquareAction>
     */
    public function getSquareActions(): array
    {
        return $this->squareActions ??= $this->squareActionRepository->findByGameWithSquare($this->game);
    }
}

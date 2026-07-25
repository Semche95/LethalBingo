<?php

namespace App\Twig\Components;

use App\Entity\BingoSquare;
use App\Entity\Game;
use App\Service\BingoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

#[AsLiveComponent(template: 'components/BingoCard/BingoCard.html.twig')]
final class BingoCard extends AbstractController
{
    use ComponentToolsTrait;

    #[LiveProp]
    public bool $editMode = false;
    #[LiveProp]
    public string $squaresFieldName = '';
    #[LiveProp]
    public array $squares;
    #[LiveProp]
    public array $duplicateCoords = [];
    #[LiveProp]
    public ?Game $game = null;

    public function __construct(
        private readonly BingoService $bingo
    )
    {
    }

    #[LiveListener('gameFrameRefresh')]
    #[LiveListener('bingoCardRefresh')]
    public function __invoke()
    {
    }

    public function getMaxRows(): int
    {
        return BingoService::MAX_ROWS;
    }

    public function getMaxCols(): int
    {
        return BingoService::MAX_COLS;
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

    public function isSquareContentShowable(int $squareId, int $row, int $col): bool
    {
        return $this->isGranted('ROLE_REFEREE') || $this->isSquareVisible($row, $col) || $this->isSquareRevealed($squareId) || $this->isGameFinished();
    }

    public function isSquareVisible(int $row, int $col): bool
    {
        return BingoService::isSquareVisible($row, $col);
    }

    public function isSquareRevealed(int $squareId): bool
    {
        $square = $this->bingo->getRevealedSquare($this->game, $squareId);
        return $square?->getRevealedAt() !== null;
    }

    public function isSquareValidated(int $squareId): bool
    {
        $square = $this->bingo->getValidatedSquare($this->game, $squareId);
        return $square?->getValidatedAt() !== null;
    }

    public function getRowLetter(int $row): string
    {
        return BingoSquare::getRowLetter($row);
    }

    private function refreshBingoCard(Game $game): void
    {
        $bingoDto = $this->bingo->entityToDto($game->getRound());
        $this->squares = $bingoDto->squares;
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function reveal(#[LiveArg] int $squareId): void
    {
        $this->bingo->reveal($this->game, $squareId);
        $this->refreshBingoCard($this->game);
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function conceal(#[LiveArg] int $squareId): void
    {
        $this->bingo->conceal($this->game, $squareId);
        $this->refreshBingoCard($this->game);
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function validate(#[LiveArg] int $squareId): void
    {
        $this->bingo->validate($this->game, $squareId);
        $this->refreshBingoCard($this->game);
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function invalidate(#[LiveArg] int $squareId): void
    {
        $this->bingo->invalidate($this->game, $squareId);
        $this->refreshBingoCard($this->game);
    }
}

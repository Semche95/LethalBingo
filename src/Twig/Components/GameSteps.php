<?php

namespace App\Twig\Components;

use App\Entity\BingoSquareCategory;
use App\Entity\Game;
use App\Repository\BingoSquareCategoryRepository;
use App\Repository\BingoSquareRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class GameSteps
{
    #[LiveProp]
    public ?Game $game = null;

    public function __construct(
        private readonly BingoSquareCategoryRepository $categoryRepository,
        private readonly BingoSquareRepository         $squareRepository,
    )
    {
    }

    #[LiveListener('gameFrameRefresh')]
    #[LiveListener('gameStepsRefresh')]
    public function __invoke()
    {
    }

    public function getCategories(): array
    {
        return $this->categoryRepository->findAllOrdered();
    }

    public function getNotValidatedSquares(BingoSquareCategory $category): array
    {
        return $this->squareRepository->findNotValidated($this->game->getRound(), $category);
    }
}

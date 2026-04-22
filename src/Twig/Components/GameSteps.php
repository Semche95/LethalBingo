<?php

namespace App\Twig\Components;

use App\Entity\BingoSquareCategory;
use App\Entity\Game;
use App\Repository\BingoSquareCategoryRepository;
use App\Repository\BingoSquareRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class GameSteps
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Game $game = null;

    public function __construct(
        private readonly BingoSquareCategoryRepository $categoryRepository,
        private readonly BingoSquareRepository $squareRepository,
    )
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

<?php

namespace App\Twig\Components;

use App\Entity\BingoSquare;
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

    /** @var list<BingoSquareCategory>|null loaded once per render */
    private ?array $categories = null;
    /** @var list<BingoSquare>|null loaded once per render */
    private ?array $notValidatedSquares = null;

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

    /**
     * @return list<BingoSquareCategory>
     */
    public function getCategories(): array
    {
        return $this->categories ??= $this->categoryRepository->findAllOrdered();
    }

    /**
     * Not validated squares of every category, loaded in a single query: the
     * template groups them by category.
     *
     * @return list<BingoSquare>
     */
    public function getNotValidatedSquares(): array
    {
        return $this->notValidatedSquares ??= $this->squareRepository->findNotValidatedWithCategory($this->game->getRound());
    }
}

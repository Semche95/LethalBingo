<?php

namespace App\Twig\Components;

use App\Repository\BingoSquareCategoryRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class BingoSquareCategorySelector
{
    public ?string $value = null;

    public function __construct(private readonly BingoSquareCategoryRepository $repository)
    {
    }

    public function getCategories(): array
    {
        return $this->repository->findAllOrdered();
    }
}

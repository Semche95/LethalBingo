<?php

namespace App\Twig\Components;

use App\Entity\BingoSquareCategory;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class BingoSquareCategorySelector
{
    public ?string $value = null;

    /**
     * Passed by the parent so that the categories are loaded once for the
     * whole grid instead of once per selector.
     *
     * @var list<BingoSquareCategory>
     */
    public array $categories = [];
}

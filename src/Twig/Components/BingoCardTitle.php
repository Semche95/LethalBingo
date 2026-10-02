<?php

namespace App\Twig\Components;

use App\Entity\Game;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class BingoCardTitle
{
    #[LiveProp]
    public ?Game $game = null;

    #[LiveListener('gameFrameRefresh')]
    #[LiveListener('bingoCardTitleRefresh')]
    public function __invoke(): void
    {
    }
}

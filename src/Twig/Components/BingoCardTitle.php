<?php

namespace App\Twig\Components;

use App\Entity\Game;
use App\Service\RefreshEvent;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class BingoCardTitle
{
    #[LiveProp]
    public ?Game $game = null;

    #[LiveListener(RefreshEvent::GameFrame->value)]
    #[LiveListener(RefreshEvent::BingoCardTitle->value)]
    public function __invoke(): void
    {
    }
}

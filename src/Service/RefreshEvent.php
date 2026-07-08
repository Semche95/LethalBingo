<?php

declare(strict_types=1);

namespace App\Service;

enum RefreshEvent: string
{
    case GameFrame = 'gameFrameRefresh';
    case GameTimer = 'gameTimerRefresh';
    case BingoCard = 'bingoCardRefresh';
    case BingoCardTitle = 'bingoCardTitleRefresh';
}

<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\TimerMode;
use App\Tests\Controller\ControllerTestCase;
use DateTimeImmutable;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class GameFrameTest extends ControllerTestCase
{
    use InteractsWithLiveComponents;

    public function testAFinishedStopwatchGameShowsItsDurationInPlaceOfTheTimer(): void
    {
        $game = $this->createGame()
            ->setTimerMode(TimerMode::Stopwatch)
            ->resetTimer(new DateTimeImmutable('2026-10-20 02:03:12'))
            ->setFinishedAt(new DateTimeImmutable('2026-10-20 03:50:02'));
        $this->em->flush();

        $crawler = $this->createLiveComponent('GameFrame', ['game' => $game], $this->client)->render()->crawler();

        self::assertCount(0, $crawler->filter('#timer-display'));
        self::assertStringContainsString('Durée de la partie 01:46:50', $crawler->filter('.card-body')->first()->text());
    }
}

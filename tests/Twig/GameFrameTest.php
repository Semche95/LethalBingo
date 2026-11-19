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

    public function testTheRefereeOpensThePointsOfAFinishedGameNextToTheHighlights(): void
    {
        $game = $this->createPlayedGame()->setFinishedAt(new DateTimeImmutable());
        $game->getRound()->addRule($this->createFreeRule('Days'));
        $this->em->flush();

        $crawler = $this->createLiveComponent('GameFrame', ['game' => $game], $this->client)
            ->actingAs($this->createUser(['ROLE_REFEREE']))
            ->render()
            ->crawler();

        self::assertCount(1, $crawler->filter('.game-footer-results [data-bs-target="#game-points-modal"]'));
        self::assertStringContainsString('Days', $crawler->filter('#game-points-modal')->text());
    }

    public function testNoPointsBeforeTheStopNorForThePlayers(): void
    {
        $game = $this->createPlayedGame();
        $game->getRound()->addRule($this->createFreeRule());
        $this->em->flush();

        $refereeView = $this->createLiveComponent('GameFrame', ['game' => $game], $this->client)
            ->actingAs($this->createUser(['ROLE_REFEREE']))
            ->render()
            ->crawler();
        self::assertCount(0, $refereeView->filter('#game-points-modal'));

        $game->setFinishedAt(new DateTimeImmutable());
        $this->em->flush();
        $this->client->getCookieJar()->clear();
        $playerView = $this->createLiveComponent('GameFrame', ['game' => $game])->render()->crawler();
        self::assertCount(0, $playerView->filter('#game-points-modal'));
    }

    public function testTheStepsAreNoLongerListedOnceTheGameIsFinished(): void
    {
        $game = $this->createPlayedGame();
        $referee = $this->createUser(['ROLE_REFEREE']);

        $crawler = $this->createLiveComponent('GameFrame', ['game' => $game], $this->client)->actingAs($referee)->render()->crawler();
        self::assertCount(1, $crawler->filter('h3:contains("Étapes")'));

        $game->setFinishedAt(new DateTimeImmutable());
        $this->em->flush();
        $crawler = $this->createLiveComponent('GameFrame', ['game' => $game], $this->client)->actingAs($referee)->render()->crawler();
        self::assertCount(0, $crawler->filter('h3:contains("Étapes")'));
    }
}

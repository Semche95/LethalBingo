<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\Game;
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

    public function testTheRefereeIsWarnedBeforeStartingWhenThePreviousRoundIsNotFinished(): void
    {
        $game = $this->createGame($this->createRound(921, 2, complete: true));
        $this->em->persist(new Game()->setTeam($game->getTeam())->setRound($this->createRound(921, 1)));
        $this->em->flush();

        $component = $this->createLiveComponent('GameFrame', ['game' => $game], $this->client)
            ->actingAs($this->createUser(['ROLE_REFEREE']));
        $crawler = $component->render()->crawler();

        self::assertCount(1, $crawler->filter('.game-footer-main[data-bs-target="#start-game-modal"]'));
        $warning = $crawler->filter('#start-game-modal')->text();
        self::assertStringContainsString($game->getTeam()->getName() . " n'a pas terminé le round 1", $warning);
        self::assertStringContainsString("sa partie n'a pas encore été démarrée", $warning);

        // Only a warning: the start still goes through.
        $component->call('startGame');
        self::assertTrue($this->reload(Game::class, (int) $game->getId())?->isStarted());
    }

    public function testTheRefereeStartsDirectlyWhenThePreviousRoundIsFinished(): void
    {
        $game = $this->createGame($this->createRound(922, 2, complete: true));
        $this->em->persist(new Game()
            ->setTeam($game->getTeam())
            ->setRound($this->createRound(922, 1))
            ->resetTimer(new DateTimeImmutable('-1 hour'))
            ->setFinishedAt(new DateTimeImmutable('-10 minutes')));
        $this->em->flush();

        $crawler = $this->createLiveComponent('GameFrame', ['game' => $game], $this->client)
            ->actingAs($this->createUser(['ROLE_REFEREE']))
            ->render()
            ->crawler();

        self::assertCount(1, $crawler->filter('.game-footer-main[data-live-action-param="startGame"]'));
        self::assertCount(0, $crawler->filter('#start-game-modal'));
    }

    public function testThePlayersNeverSeeThePreviousRoundWarning(): void
    {
        $game = $this->createGame($this->createRound(923, 2, complete: true));

        $crawler = $this->createLiveComponent('GameFrame', ['game' => $game])->render()->crawler();

        self::assertCount(0, $crawler->filter('#start-game-modal'));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\Team;
use App\Service\GameService;
use App\Service\GameTimerService;
use App\Twig\Components\GameTimer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class GameTimerLiveRenderTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testLiveReRenderNeverWritesTheTimeDisplayElement(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $suffix = bin2hex(random_bytes(6));
        $slug = 'live-render-' . $suffix;
        $team = new Team();
        $team->setName('Test team ' . $suffix);
        $round = new GameRound();
        $round->setNumber(1);
        $round->setSeason(2026);
        $game = new Game();
        $game->setSlug($slug);
        $game->setTeam($team);
        $game->setRound($round);
        $game->setStartedAt(new DateTimeImmutable('-10 minutes'));

        $em->persist($team);
        $em->persist($round);
        $em->persist($game);
        $em->flush();

        try {
            $component = $this->createLiveComponent('GameTimer', ['game' => $game]);

            $firstRender = $component->render();

            $display = $firstRender->crawler()->filter('[data-game--game-timer-target="timerDisplay"]');
            $this->assertCount(1, $display);
            $this->assertSame('', trim($display->text()));
            $this->assertNotNull($display->attr('data-live-ignore'), 'The time display element must be excluded from Live morphs.');

            $wrapper = $firstRender->crawler()->filter('#timer-display');
            $this->assertNull($wrapper->attr('data-live-ignore'), 'data-live-ignore must target the display element, not its wrapper.');

            $pauseBadge = $firstRender->crawler()->filter('[data-game--game-timer-target="pauseBadge"]');
            $this->assertNull($pauseBadge->attr('data-live-ignore'), 'The PAUSE badge must stay morphable.');

            $serverTimeAttr = $firstRender->crawler()->filter('[data-game--game-timer-server-time-value]')->attr('data-game--game-timer-server-time-value');
            $this->assertNotEmpty($serverTimeAttr);

            $sharedServerTime = 1_700_000_000.125;
            $component->emit('gameTimerRefresh', ['serverTime' => $sharedServerTime]);

            $secondRender = $component->render();

            $displayAfterRefresh = $secondRender->crawler()->filter('[data-game--game-timer-target="timerDisplay"]');
            $this->assertCount(1, $displayAfterRefresh);
            $this->assertSame('', trim($displayAfterRefresh->text()), 'A Live re-render must never write a value into the time display element.');
            $this->assertNotNull($displayAfterRefresh->attr('data-live-ignore'));

            $serverTimeAttrAfterRefresh = $secondRender->crawler()->filter('[data-game--game-timer-server-time-value]')->attr('data-game--game-timer-server-time-value');
            $this->assertSame((string) $sharedServerTime, $serverTimeAttrAfterRefresh);
        } finally {
            $cleanupEm = self::getContainer()->get(EntityManagerInterface::class);
            $cleanupGame = $cleanupEm->getRepository(Game::class)->findOneBy(['slug' => $slug]);

            if ($cleanupGame instanceof Game) {
                $roundToRemove = $cleanupGame->getRound();
                $teamToRemove = $cleanupGame->getTeam();

                $cleanupEm->remove($cleanupGame);
                $cleanupEm->flush();

                if ($roundToRemove instanceof GameRound) {
                    $cleanupEm->remove($roundToRemove);
                }

                if ($teamToRemove instanceof Team) {
                    $cleanupEm->remove($teamToRemove);
                }

                $cleanupEm->flush();
            }
        }
    }

    public function testInvokeWithoutServerTimeSetsFreshAnchor(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $hub = $this->createStub(HubInterface::class);
        $gameService = new GameService($em, $hub);
        $timerService = new GameTimerService($gameService);
        $component = new GameTimer($timerService, $em);

        // Simulate a stale anchor (e.g. from mount() some time ago).
        $component->serverTime = 1_700_000_000.0;

        $before = microtime(true);
        $component->__invoke();
        $after = microtime(true);

        $this->assertGreaterThan(0.0, $component->serverTime);
        $this->assertGreaterThanOrEqual($before, $component->serverTime);
        $this->assertLessThanOrEqual($after, $component->serverTime);
    }
}

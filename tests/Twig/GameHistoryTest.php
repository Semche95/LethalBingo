<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\BingoSquare;
use App\Entity\Game;
use App\Entity\GameHighlight;
use App\Entity\GameSquareAction;
use App\Entity\SquareActionType;
use App\Tests\Controller\ControllerTestCase;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class GameHistoryTest extends ControllerTestCase
{
    use InteractsWithLiveComponents;

    public function testListsTheActionsAndHighlightsFromTheOldestToTheNewest(): void
    {
        $this->loginAsReferee();
        $startedAt = self::secondsAgo(600);
        $game = $this->createGame()->setStartedAt($startedAt);
        $this->createAction($game, 1, 1, SquareActionType::Reveal, self::secondsAgo(720));
        $this->createAction($game, 1, 1, SquareActionType::Conceal, self::secondsAgo(660));
        $this->createAction($game, 2, 2, SquareActionType::Invalidate, $startedAt->modify('+300 seconds'));
        $this->createAction($game, 1, 3, SquareActionType::Validate, $startedAt->modify('+65 seconds'));
        $this->em->persist(new GameHighlight($game, $startedAt->modify('+120 seconds'))->setNote('Premier mort'));
        $this->em->persist(new GameHighlight($game, $startedAt->modify('+200 seconds')));
        $this->em->flush();

        $crawler = $this->renderHistory($game);

        self::assertSame('6', $crawler->filter('.game-history-header .badge')->text());
        self::assertStringContainsString('Partie en cours', $crawler->filter('.game-history-header')->text());
        self::assertCount(1, $crawler->filter('.game-history-header .status-dot-animated'));
        $clock = static fn (int $seconds): string => $startedAt->modify($seconds . ' seconds')->format('H:i:s');
        self::assertSame([
            $clock(-120) . ' A1 dévoilée ' . $this->squareLabel($game, 1, 1),
            $clock(-60) . ' A1 masquée ' . $this->squareLabel($game, 1, 1),
            'Lancement de la partie · ' . $clock(0),
            '00:01:05 ' . $clock(65) . ' A3 validée ' . $this->squareLabel($game, 1, 3),
            '00:02:00 ' . $clock(120) . ' Temps fort Premier mort',
            '00:03:20 ' . $clock(200) . ' Temps fort Sans note',
            '00:05:00 ' . $clock(300) . ' B2 invalidée ' . $this->squareLabel($game, 2, 2),
        ], $crawler->filter('.game-history-list > li')->each(static fn (Crawler $row): string => $row->text()));
        self::assertCount(2, $crawler->filter('.game-history-highlight'));
        self::assertCount(2, $crawler->filter('.game-history-time > span.text-secondary'), 'The actions done before the launch only show their time, in grey.');
        self::assertSame(
            $startedAt->modify('+65 seconds')->format('d/m/Y à H:i:s'),
            $crawler->filter('.game-history-time')->eq(2)->attr('title'),
        );
    }

    public function testExplainsWhatTheHistoryWillShowBeforeAnyAction(): void
    {
        $this->loginAsReferee();

        $crawler = $this->renderHistory($this->createGame());

        self::assertSame('0', $crawler->filter('.game-history-header .badge')->text());
        self::assertStringContainsString('Partie non démarrée', $crawler->filter('.game-history-header')->text());
        self::assertCount(0, $crawler->filter('.game-history-list'));
        self::assertStringContainsString("Aucune action pour l'instant.", $crawler->filter('.game-history-empty')->text());
    }

    public function testStaysReadableOnceTheGameIsOver(): void
    {
        $this->loginAsReferee();
        $startedAt = self::secondsAgo(600);
        $game = $this->createGame()->setStartedAt($startedAt)->setFinishedAt(self::secondsAgo(60));
        $this->createAction($game, 1, 1, SquareActionType::Reveal, $startedAt->modify('+30 seconds'));

        $crawler = $this->renderHistory($game);

        self::assertStringContainsString('Partie terminée', $crawler->filter('.game-history-header')->text());
        self::assertCount(0, $crawler->filter('.game-history-header .status-dot'));
        self::assertSame(
            ['Lancement de la partie · ' . $startedAt->format('H:i:s'), '00:00:30 ' . $startedAt->modify('+30 seconds')->format('H:i:s') . ' A1 dévoilée ' . $this->squareLabel($game, 1, 1)],
            $crawler->filter('.game-history-list > li')->each(static fn (Crawler $row): string => $row->text()),
        );
    }

    public function testIsNeverSentToThePlayers(): void
    {
        $game = $this->createGame()->setStartedAt(self::secondsAgo(600));
        $this->createAction($game, 1, 1, SquareActionType::Reveal, self::secondsAgo(300));

        $crawler = $this->createLiveComponent('GameFrame', ['game' => $game], $this->client)->render()->crawler();

        self::assertCount(1, $crawler->filter('#square11'));
        self::assertCount(0, $crawler->filter('.game-history-panel'));

        // A player requesting the history of the game gets nothing.
        $this->expectException(AccessDeniedException::class);
        $this->createLiveComponent('GameHistory', ['game' => $game], $this->client)->render();
    }

    private function renderHistory(Game $game): Crawler
    {
        $this->em->clear();

        return $this->createLiveComponent('GameHistory', ['game' => $this->reload(Game::class, (int) $game->getId())], $this->client)
            ->render()
            ->crawler();
    }

    private function createAction(Game $game, int $row, int $col, SquareActionType $type, DateTimeImmutable $occurredAt): void
    {
        $this->em->persist(new GameSquareAction($game, $this->square($game, $row, $col), $type)->setOccurredAt($occurredAt));
        $this->em->flush();
    }

    private function squareLabel(Game $game, int $row, int $col): string
    {
        return $this->square($game, $row, $col)->getLabel();
    }

    private function square(Game $game, int $row, int $col): BingoSquare
    {
        return $this->em->getRepository(BingoSquare::class)->findOneBy(['round' => $game->getRound(), 'row' => $row, 'col' => $col]);
    }

    /**
     * Dates are stored to the second.
     */
    private static function secondsAgo(int $seconds): DateTimeImmutable
    {
        return new DateTimeImmutable(date('Y-m-d H:i:s', time() - $seconds));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\Game;
use App\Tests\Controller\ControllerTestCase;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

/**
 * The game components are re-rendered by every client at each Mercure event:
 * their number of SQL queries must not grow with the number of squares.
 */
class GameComponentsQueryCountTest extends ControllerTestCase
{
    use InteractsWithLiveComponents;

    public function testPlayerBingoCardRerenderRunsAFixedNumberOfQueries(): void
    {
        $game = $this->createPlayedGame();

        $this->rerender('BingoCard', $game, 'bingoCardRefresh');

        // game, round, squares, revealed ids, validated ids
        $this->assertQueryCountAtMost(5);
    }

    public function testRefereeBingoCardRerenderRunsAFixedNumberOfQueries(): void
    {
        $this->loginAsReferee();
        $game = $this->createPlayedGame();

        $this->rerender('BingoCard', $game, 'bingoCardRefresh');

        // user, game, round, squares, revealed ids, validated ids
        $this->assertQueryCountAtMost(6);
    }

    public function testGameStepsRerenderRunsAFixedNumberOfQueries(): void
    {
        $this->loginAsReferee();
        $game = $this->createPlayedGame();

        $this->rerender('GameSteps', $game, 'gameStepsRefresh');

        // user, game, categories, not validated squares
        $this->assertQueryCountAtMost(4);
    }

    public function testGameHistoryRerenderRunsAFixedNumberOfQueries(): void
    {
        $this->loginAsReferee();
        $game = $this->createPlayedGame();

        $this->rerender('GameHistory', $game, 'gameHistoryRefresh');

        // user, game, actions with their square, highlights
        $this->assertQueryCountAtMost(4);
    }

    public function testRefereeGameFrameBeforeTheStartLooksUpThePreviousRoundInOneQuery(): void
    {
        $this->loginAsReferee();
        $game = $this->createGame($this->createRound(931, 2, complete: true));
        $this->countNextRequestQueries();

        $this->client->request('GET', '/game/' . $game->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->previousRoundLookups());
        // user, game, previous round games, revealed ids, squares, validated ids,
        // not validated squares, categories, actions, highlights
        $this->assertQueryCountAtMost(10);
    }

    public function testPlayerGameFrameNeverLooksUpThePreviousRound(): void
    {
        $game = $this->createGame($this->createRound(932, 2, complete: true));
        $this->countNextRequestQueries();

        $this->client->request('GET', '/game/' . $game->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->previousRoundLookups());
        // game, revealed ids, squares, validated ids
        $this->assertQueryCountAtMost(4);
    }

    public function testGameStepsListsTheNotValidatedSquaresUnderTheirCategory(): void
    {
        $this->loginAsReferee();
        $game = $this->createPlayedGame();
        $other = new BingoSquareCategory()->setLabel('Other category')->setPosition(99);
        $this->em->persist($other);
        $this->em->getRepository(BingoSquare::class)
            ->findOneBy(['round' => $game->getRound(), 'row' => 5, 'col' => 5])
            ->setCategory($other);
        $this->em->flush();
        $defaultCategory = (string) $game->getRound()->getBingoSquares()->first()->getCategory();

        $crawler = $this->createLiveComponent('GameSteps', ['game' => $game], $this->client)->render()->crawler();

        $squaresOf = static fn (string $category): array => $crawler
            ->filter('.sidebar-category')
            ->reduce(static fn ($item): bool => $item->filter('span')->first()->text() === $category)
            ->filter('a')
            ->each(static fn ($link): string => $link->text());

        self::assertSame(['E5'], $squaresOf('Other category'));
        $defaultSquares = $squaresOf($defaultCategory);
        self::assertCount(22, $defaultSquares);
        self::assertNotContains('A3', $defaultSquares, 'A validated square is not a step anymore.');
        self::assertNotContains('B2', $defaultSquares, 'A validated square is not a step anymore.');
        self::assertNotContains('E5', $defaultSquares);
    }

    public function testGameStepsIgnoreTheSquaresValidatedInAnotherGameOfTheSameRound(): void
    {
        $this->loginAsReferee();
        $game = $this->createPlayedGame();
        $otherGame = $this->createGame($game->getRound())->setStartedAt(new DateTimeImmutable('-5 minutes'));
        $this->validateSquare($otherGame, 4, 4);

        $crawler = $this->createLiveComponent('GameSteps', ['game' => $game], $this->client)->render()->crawler();

        $steps = $crawler->filter('.sidebar-category a')->each(static fn ($link): string => $link->text());
        self::assertContains('D4', $steps, 'A square validated by another team is still a step of this game.');
        self::assertNotContains('A3', $steps);
    }

    private function rerender(string $component, Game $game, string $event): void
    {
        $liveComponent = $this->createLiveComponent($component, ['game' => $game], $this->client);
        $liveComponent->render();

        $this->countNextRequestQueries();
        $liveComponent->emit($event);

        self::assertResponseIsSuccessful();
    }

    /**
     * Queries of the last request that look up a round by its number, as
     * GameRepository::findPreviousRoundGames() does.
     */
    private function previousRoundLookups(): int
    {
        $collector = $this->client->getProfile()?->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);
        $queries = array_column($collector->getQueries()['default'] ?? [], 'sql');

        return \count(preg_grep('/\\.number = \\?/', $queries) ?: []);
    }
}

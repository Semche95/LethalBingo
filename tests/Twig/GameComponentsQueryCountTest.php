<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\Game;
use App\Tests\Controller\ControllerTestCase;
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

    private function rerender(string $component, Game $game, string $event): void
    {
        $liveComponent = $this->createLiveComponent($component, ['game' => $game], $this->client);
        $liveComponent->render();

        $this->countNextRequestQueries();
        $liveComponent->emit($event);

        self::assertResponseIsSuccessful();
    }
}

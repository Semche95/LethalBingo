<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareRevealed;
use App\Entity\Game;
use DateTimeImmutable;

class GameControllerTest extends ControllerTestCase
{
    public function testGamePageIsPublic(): void
    {
        $game = $this->createGame();

        $this->client->request('GET', '/game/' . $game->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('[data-controller~="game--game-notes"]');
    }

    public function testRefereeSeesTheNotesPanelOnTheGamePage(): void
    {
        $this->loginAsReferee();
        $game = $this->createGame();

        $this->client->request('GET', '/game/' . $game->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-controller~="game--game-notes"]');
    }

    public function testPlayerReceivesNoLabelBeforeTheStart(): void
    {
        $game = $this->createGame();

        $this->client->request('GET', '/game/' . $game->getSlug());

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Square ', $this->client->getResponse()->getContent());
    }

    public function testPlayerReceivesOnlyVisibleAndRevealedLabelsDuringTheGame(): void
    {
        $game = $this->createGame()->setStartedAt(new DateTimeImmutable());
        $this->revealSquare($game, 1, 1);

        $this->client->request('GET', '/game/' . $game->getSlug());

        $html = $this->client->getResponse()->getContent();
        self::assertStringContainsString('Square 1-3', $html);
        self::assertStringContainsString('Square 1-1', $html);
        self::assertStringNotContainsString('Square 1-2', $html);
        self::assertStringNotContainsString('Square 5-5', $html);
    }

    public function testPlayerReceivesEveryLabelOnceTheGameIsOver(): void
    {
        $game = $this->createGame()
            ->setStartedAt(new DateTimeImmutable('-1 hour'))
            ->setFinishedAt(new DateTimeImmutable());
        $this->em->flush();

        $this->client->request('GET', '/game/' . $game->getSlug());

        self::assertStringContainsString('Square 1-2', $this->client->getResponse()->getContent());
    }

    public function testRefereeReceivesEveryLabelBeforeTheStart(): void
    {
        $this->loginAsReferee();
        $game = $this->createGame();

        $this->client->request('GET', '/game/' . $game->getSlug());

        self::assertStringContainsString('Square 1-2', $this->client->getResponse()->getContent());
    }

    public function testUnknownSlugReturnsNotFound(): void
    {
        $this->client->request('GET', '/game/unknownSlug0');

        self::assertResponseStatusCodeSame(404);
    }

    public function testRefereeSavesTheNotes(): void
    {
        $this->loginAsReferee();
        $game = $this->createGame();

        $this->postNotes($game, ['notes' => 'Team is late'], $this->fetchNotesToken($game));

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], json_decode($this->client->getResponse()->getContent(), true));
        self::assertSame('Team is late', $this->reload(Game::class, $game->getId())->getNotes());
    }

    public function testMissingNotesClearTheNotes(): void
    {
        $this->loginAsReferee();
        $game = $this->createGame()->setNotes('Old notes');

        $this->postNotes($game, [], $this->fetchNotesToken($game));

        self::assertResponseIsSuccessful();
        self::assertSame('', $this->reload(Game::class, $game->getId())->getNotes());
    }

    public function testSaveNotesWithAnInvalidTokenIsRejected(): void
    {
        $this->loginAsReferee();
        $game = $this->createGame();

        $this->postNotes($game, ['notes' => 'Forged'], 'invalid');

        self::assertResponseStatusCodeSame(400);
        self::assertNull($this->reload(Game::class, $game->getId())->getNotes());
    }

    public function testPlayerCannotSaveNotes(): void
    {
        $this->client->loginUser($this->createUser());
        $game = $this->createGame();

        $this->postNotes($game, ['notes' => 'Hacked']);

        self::assertResponseStatusCodeSame(403);
        self::assertNull($this->reload(Game::class, $game->getId())->getNotes());
    }

    public function testAnonymousVisitorCannotSaveNotes(): void
    {
        $game = $this->createGame();

        $this->postNotes($game, ['notes' => 'Hacked']);

        self::assertResponseRedirects('/user/login');
        self::assertNull($this->reload(Game::class, $game->getId())->getNotes());
    }

    private function fetchNotesToken(Game $game): string
    {
        $crawler = $this->client->request('GET', '/game/' . $game->getSlug());

        return (string) $crawler->filter('[data-game--game-notes-target="token"]')->attr('value');
    }

    private function revealSquare(Game $game, int $row, int $col): void
    {
        $square = $this->em->getRepository(BingoSquare::class)->findOneBy(['round' => $game->getRound(), 'row' => $row, 'col' => $col]);
        $this->em->persist(new BingoSquareRevealed()
            ->setGame($game)
            ->setBingoSquare($square)
            ->setRevealedAt(new DateTimeImmutable()));
        $this->em->flush();
    }

    /**
     * @param array<string, string> $payload
     */
    private function postNotes(Game $game, array $payload, string $token = ''): void
    {
        $this->client->request(
            'POST',
            '/game/save-notes/' . $game->getId(),
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $token],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}

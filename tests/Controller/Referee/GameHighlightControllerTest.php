<?php

declare(strict_types=1);

namespace App\Tests\Controller\Referee;

use App\Entity\Game;
use App\Entity\GameHighlight;
use App\Tests\Controller\ControllerTestCase;
use DateTimeImmutable;

class GameHighlightControllerTest extends ControllerTestCase
{
    public function testRefereeMarksAHighlightDuringThePause(): void
    {
        $this->loginAsReferee();
        $game = $this->createGameInProgress()
            ->setIsPaused(true)
            ->setLastPausedAt(new DateTimeImmutable('-1 minute'));
        $this->em->flush();
        $token = $this->fetchHighlightsToken($game);

        $this->post('/referee/game/' . $game->getId() . '/highlight', $token);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('li.game-highlight input[aria-label^="Note du temps fort 00:12:"]');
        $highlights = $this->findHighlights($game);
        self::assertCount(1, $highlights);
        self::assertGreaterThanOrEqual(12 * 60, $highlights[0]->getElapsedSeconds());
        self::assertNull($highlights[0]->getNote());
    }

    public function testRefereeSavesAndClearsTheNoteOfAHighlight(): void
    {
        $this->loginAsReferee();
        $game = $this->createGameInProgress();
        $highlight = $this->createHighlight($game);
        $token = $this->fetchHighlightsToken($game);

        $this->post('/referee/game/highlight/' . $highlight->getId() . '/note', $token, ['note' => 'Fou rire']);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('Fou rire', $this->reload(GameHighlight::class, $highlight->getId())->getNote());

        $this->post('/referee/game/highlight/' . $highlight->getId() . '/note', $token, ['note' => '']);

        self::assertNull($this->reload(GameHighlight::class, $highlight->getId())->getNote());
    }

    public function testRefereeDeletesAHighlight(): void
    {
        $this->loginAsReferee();
        $game = $this->createGameInProgress();
        $highlight = $this->createHighlight($game);
        $token = $this->fetchHighlightsToken($game);

        $this->post('/referee/game/highlight/' . $highlight->getId() . '/delete', $token);

        self::assertResponseStatusCodeSame(204);
        self::assertNull($this->reload(GameHighlight::class, $highlight->getId()));
    }

    public function testRequestWithAnInvalidTokenIsRejected(): void
    {
        $this->loginAsReferee();
        $game = $this->createGameInProgress();

        $this->post('/referee/game/' . $game->getId() . '/highlight', 'invalid');

        self::assertResponseStatusCodeSame(400);
        self::assertCount(0, $this->findHighlights($game));
    }

    public function testHighlightsCannotBeChangedOnceTheGameIsOver(): void
    {
        $this->loginAsReferee();
        $game = $this->createGameInProgress();
        $highlight = $this->createHighlight($game, 'Kept');
        $token = $this->fetchHighlightsToken($game);
        $game->setFinishedAt(new DateTimeImmutable());
        $this->em->flush();

        $this->post('/referee/game/' . $game->getId() . '/highlight', $token);
        self::assertResponseStatusCodeSame(400);
        $this->post('/referee/game/highlight/' . $highlight->getId() . '/note', $token, ['note' => 'Changed']);
        self::assertResponseStatusCodeSame(400);
        $this->post('/referee/game/highlight/' . $highlight->getId() . '/delete', $token);
        self::assertResponseStatusCodeSame(400);

        self::assertSame('Kept', $this->reload(GameHighlight::class, $highlight->getId())->getNote());
    }

    public function testFinishedGameListsTheHighlightsReadOnly(): void
    {
        $this->loginAsReferee();
        $game = $this->createGameInProgress();
        $this->createHighlight($game, 'Retournement');
        $game->setFinishedAt(new DateTimeImmutable());
        $this->em->flush();

        $this->client->request('GET', '/game/' . $game->getSlug());

        self::assertSelectorTextContains('li.game-highlight', 'Retournement');
        self::assertSelectorNotExists('[data-action="game--game-highlights#mark"]');
        self::assertSelectorNotExists('li.game-highlight input');
        self::assertSelectorNotExists('.game-highlight-delete');
    }

    public function testListButtonIsDisabledWithoutHighlight(): void
    {
        $this->loginAsReferee();
        $game = $this->createGameInProgress();

        $this->client->request('GET', '/game/' . $game->getSlug());

        self::assertSelectorExists('[data-game--game-highlights-target="listButton"][disabled]');
        self::assertSelectorExists('[data-game--game-highlights-target="listTooltip"][data-bs-title="Aucun temps fort"]');
    }

    public function testPlayerSeesNoHighlight(): void
    {
        $game = $this->createGameInProgress();
        $this->createHighlight($game, 'Secret');

        $this->client->request('GET', '/game/' . $game->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('[data-controller~="game--game-highlights"]');
        self::assertStringNotContainsString('Secret', $this->client->getResponse()->getContent());
    }

    public function testPlayerCannotMarkAHighlight(): void
    {
        $this->client->loginUser($this->createUser());
        $game = $this->createGameInProgress();

        $this->post('/referee/game/' . $game->getId() . '/highlight', '');

        self::assertResponseStatusCodeSame(403);
        self::assertCount(0, $this->findHighlights($game));
    }

    private function createGameInProgress(): Game
    {
        $game = $this->createGame()->setStartedAt(new DateTimeImmutable('-12 minutes'));
        $this->em->flush();

        return $game;
    }

    private function createHighlight(Game $game, ?string $note = null): GameHighlight
    {
        $highlight = new GameHighlight($game)->setNote($note);
        // The requests share the identity map: the game must already list its highlight.
        $game->getHighlights()->add($highlight);
        $this->em->persist($highlight);
        $this->em->flush();

        return $highlight;
    }

    /**
     * @return list<GameHighlight>
     */
    private function findHighlights(Game $game): array
    {
        $this->em->clear();

        return $this->em->getRepository(GameHighlight::class)->findBy(['game' => $game->getId()]);
    }

    private function fetchHighlightsToken(Game $game): string
    {
        $crawler = $this->client->request('GET', '/game/' . $game->getSlug());

        return (string) $crawler->filter('[data-game--game-highlights-target="token"]')->attr('value');
    }

    /**
     * @param array<string, string>|null $payload
     */
    private function post(string $url, string $token, ?array $payload = null): void
    {
        $this->client->request(
            'POST',
            $url,
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $token],
            content: $payload === null ? null : json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Controller\Referee;

use App\Entity\Game;
use App\Tests\Controller\ControllerTestCase;
use DateTimeImmutable;

class GameControllerTest extends ControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsReferee();
    }

    public function testCreatesAGameWithACompleteBingo(): void
    {
        $team = $this->createTeam();
        $round = $this->createRound(1, 1, complete: true);

        $this->submitGameForm('/referee/game/add', $team->getId(), $round->getId());

        $game = $this->em->getRepository(Game::class)->findOneBy(['team' => $team]);
        self::assertNotNull($game);
        self::assertSame($round->getId(), $game->getRound()->getId());
        self::assertResponseRedirects('/referee/game/edit/' . $game->getId());
        $this->client->followRedirect();
        self::assertFlash('success', 'Partie enregistrée');
    }

    public function testRefusesAnIncompleteBingo(): void
    {
        $team = $this->createTeam();
        $round = $this->createRound(1, 1);

        $this->submitGameForm('/referee/game/add', $team->getId(), $round->getId());

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback', 'Veuillez sélectionner un bingo.');
        self::assertNull($this->em->getRepository(Game::class)->findOneBy(['team' => $team]));
    }

    public function testRejectsAnEmptyForm(): void
    {
        $gameCount = $this->em->getRepository(Game::class)->count();
        $crawler = $this->client->request('GET', '/referee/game/add');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['game_form[team]'] = '';
        $form['game_form[round]'] = '';

        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertAnySelectorTextContains('.invalid-feedback', 'Veuillez sélectionner un bingo.');
        self::assertAnySelectorTextContains('.invalid-feedback', 'Veuillez sélectionner une équipe.');
        self::assertSame($gameCount, $this->em->getRepository(Game::class)->count());
    }

    public function testEditsAGameThatHasNotStarted(): void
    {
        $game = $this->createGame();
        $otherTeam = $this->createTeam();

        $this->submitGameForm('/referee/game/edit/' . $game->getId(), $otherTeam->getId(), $game->getRound()->getId());

        self::assertResponseRedirects('/referee/game/edit/' . $game->getId());
        self::assertSame($otherTeam->getId(), $this->reload(Game::class, $game->getId())->getTeam()->getId());
    }

    public function testEditFormIsPrefilledWithTheGame(): void
    {
        $game = $this->createGame();

        $this->client->request('GET', '/referee/game/edit/' . $game->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('select[name="game_form[team]"] option[value="%d"][selected]', $game->getTeam()->getId()));
        self::assertSelectorExists(sprintf('select[name="game_form[round]"] option[value="%d"][selected]', $game->getRound()->getId()));
    }

    public function testEditRejectsAnEmptyFormAndKeepsTheGame(): void
    {
        $game = $this->createGame();
        $teamId = $game->getTeam()->getId();
        $roundId = $game->getRound()->getId();
        $crawler = $this->client->request('GET', '/referee/game/edit/' . $game->getId());
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['game_form[team]'] = '';
        $form['game_form[round]'] = '';

        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertAnySelectorTextContains('.invalid-feedback', 'Veuillez sélectionner une équipe.');
        self::assertAnySelectorTextContains('.invalid-feedback', 'Veuillez sélectionner un bingo.');
        $reloaded = $this->reload(Game::class, $game->getId());
        self::assertSame($teamId, $reloaded->getTeam()->getId());
        self::assertSame($roundId, $reloaded->getRound()->getId());
    }

    public function testStartedGameIsReadOnly(): void
    {
        $game = $this->createGame()->setStartedAt(new DateTimeImmutable());
        $this->em->flush();

        $this->client->request('GET', '/referee/game/edit/' . $game->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="game_form[team]"][disabled]');
        self::assertSelectorNotExists('button[type="submit"].btn-success');
        self::assertSelectorNotExists('[data-bs-target="#delete-game-modal"]');
    }

    public function testDeletesAGame(): void
    {
        $game = $this->createGame();
        $gameId = $game->getId();
        $token = $this->fetchToken('/referee/game/edit/' . $gameId, '#delete-game-modal');

        $this->client->request('POST', '/referee/game/delete/' . $gameId, ['token' => $token]);

        self::assertResponseRedirects('/referee/game');
        self::assertNull($this->reload(Game::class, $gameId));
    }

    public function testDeleteWithAnInvalidTokenIsRejected(): void
    {
        $game = $this->createGame();

        $this->client->request('POST', '/referee/game/delete/' . $game->getId(), ['token' => 'invalid']);

        self::assertResponseStatusCodeSame(400);
        self::assertNotNull($this->reload(Game::class, $game->getId()));
    }

    public function testStartedGameCannotBeDeleted(): void
    {
        $game = $this->createGame();
        $token = $this->fetchToken('/referee/game/edit/' . $game->getId(), '#delete-game-modal');
        $game->setStartedAt(new DateTimeImmutable());
        $this->em->flush();

        $this->client->request('POST', '/referee/game/delete/' . $game->getId(), ['token' => $token]);

        self::assertResponseStatusCodeSame(400);
        self::assertNotNull($this->reload(Game::class, $game->getId()));
    }

    public function testRegeneratesTheGameLink(): void
    {
        $game = $this->createGame();
        $oldSlug = $game->getSlug();
        $token = $this->fetchToken('/referee/game/edit/' . $game->getId(), '#regenerate-slug-modal');

        $this->client->request('POST', '/referee/game/regenerate-slug/' . $game->getId(), ['token' => $token]);

        self::assertResponseRedirects('/referee/game/edit/' . $game->getId());
        $newSlug = $this->reload(Game::class, $game->getId())->getSlug();
        self::assertNotSame($oldSlug, $newSlug);
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9]{12}$/', $newSlug);
    }

    public function testRegenerateWithAnInvalidTokenIsRejected(): void
    {
        $game = $this->createGame();

        $this->client->request('POST', '/referee/game/regenerate-slug/' . $game->getId(), ['token' => 'invalid']);

        self::assertResponseStatusCodeSame(400);
        self::assertSame($game->getSlug(), $this->reload(Game::class, $game->getId())->getSlug());
    }

    private function submitGameForm(string $url, int $teamId, int $roundId): void
    {
        $crawler = $this->client->request('GET', $url);
        $form = $crawler->selectButton('Enregistrer')->form();
        // Incomplete bingos are disabled options the browser would not send.
        $form->disableValidation();
        $form['game_form[team]'] = (string) $teamId;
        $form['game_form[round]'] = (string) $roundId;

        $this->client->submit($form);
    }
}

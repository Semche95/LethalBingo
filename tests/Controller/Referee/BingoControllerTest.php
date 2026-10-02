<?php

declare(strict_types=1);

namespace App\Tests\Controller\Referee;

use App\Entity\BingoSquareCategory;
use App\Entity\GameRound;
use App\Tests\Controller\ControllerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class BingoControllerTest extends ControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsReferee();
    }

    public function testCreatesABingo(): void
    {
        $categoryId = $this->em->getRepository(BingoSquareCategory::class)->findOneBy([])->getId();

        $this->submitBingoForm('/referee/bingo/add', 12, 4, [
            '[1][1][label]' => 'Kill a Bracken',
            '[1][1][category]' => (string) $categoryId,
        ]);

        $round = $this->findRound(12, 4);
        self::assertNotNull($round);
        self::assertResponseRedirects('/referee/bingo/edit/' . $round->getId());
        self::assertCount(GameRound::EXPECTED_SQUARES, $round->getBingoSquares());
        $square = $round->getBingoSquares()->findFirst(static fn (int $key, $square): bool => $square->getRow() === 1 && $square->getCol() === 1);
        self::assertSame('Kill a Bracken', $square->getLabel());
        self::assertSame($categoryId, $square->getCategory()->getId());
        self::assertFalse($square->isVisible());
        $visibleSquare = $round->getBingoSquares()->findFirst(static fn (int $key, $square): bool => $square->getRow() === 1 && $square->getCol() === 3);
        self::assertTrue($visibleSquare->isVisible());
        self::assertNull($visibleSquare->getCategory());
    }

    public function testRejectsDuplicateLabels(): void
    {
        $this->submitBingoForm('/referee/bingo/add', 12, 4, [
            '[1][1][label]' => 'Same label',
            '[2][3][label]' => 'Same label',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.alert-danger', 'Des labels sont dupliqués dans le bingo.');
        self::assertNull($this->findRound(12, 4));
    }

    public function testRejectsAnEmptySeasonAndRound(): void
    {
        $crawler = $this->client->request('GET', '/referee/bingo/add');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form->disableValidation();
        $form['bingo_form[season]'] = '';
        $form['bingo_form[round]'] = '';

        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertAnySelectorTextContains('.invalid-feedback', 'Veuillez saisir une saison.');
        self::assertAnySelectorTextContains('.invalid-feedback', 'Veuillez saisir un numéro de round.');
    }

    public function testEditPageRunsAFixedNumberOfQueries(): void
    {
        $round = $this->createRound(12, 4, complete: true);

        $this->countNextRequestQueries();
        $this->client->request('GET', '/referee/bingo/edit/' . $round->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(GameRound::EXPECTED_SQUARES, '#bingo-form select');
        // user, round with its squares, categories shared by the 25 selectors
        $this->assertQueryCountAtMost(3);
    }

    public function testEditsABingo(): void
    {
        $round = $this->createRound(12, 4, complete: true);

        $this->submitBingoForm('/referee/bingo/edit/' . $round->getId(), 13, 5, [
            '[1][1][label]' => 'Renamed square',
        ]);

        self::assertResponseRedirects('/referee/bingo/edit/' . $round->getId());
        $reloaded = $this->reload(GameRound::class, $round->getId());
        self::assertSame(13, $reloaded->getSeason());
        self::assertSame(5, $reloaded->getNumber());
        self::assertCount(GameRound::EXPECTED_SQUARES, $reloaded->getBingoSquares());
        self::assertContains('Renamed square', $reloaded->getBingoSquares()->map(static fn ($square) => $square->getLabel())->toArray());
    }

    public function testAddsTheNextRoundOfASeason(): void
    {
        $this->createRound(12, 1);
        $this->createRound(12, 2);
        $token = $this->fetchToken('/referee/bingo', 'form[action="/referee/bingo/add-round/12"]');

        $this->client->request('POST', '/referee/bingo/add-round/12', ['token' => $token]);

        $round = $this->findRound(12, 3);
        self::assertNotNull($round);
        self::assertResponseRedirects('/referee/bingo/edit/' . $round->getId());
        $this->client->followRedirect();
        self::assertFlash('success', 'Round 3 de la saison 12 créé');
    }

    public function testAddsANewSeasonStartingAtRoundOne(): void
    {
        $this->createRound(12, 1);
        $token = $this->fetchToken('/referee/bingo', 'form[action="/referee/bingo/add-season"]');

        $this->client->request('POST', '/referee/bingo/add-season', ['token' => $token]);

        self::assertResponseRedirects('/referee/bingo');
        self::assertNotNull($this->findRound(13, 1));
        $this->client->followRedirect();
        self::assertFlash('success', 'Saison 13 créée (round 1)');
    }

    #[DataProvider('creationUrlsProvider')]
    public function testCreationWithAnInvalidTokenIsRejected(string $url): void
    {
        $this->createRound(12, 1);

        $this->client->request('POST', $url, ['token' => 'invalid']);

        self::assertResponseStatusCodeSame(400);
        self::assertNull($this->findRound(12, 2));
        self::assertNull($this->findRound(13, 1));
    }

    #[DataProvider('creationUrlsProvider')]
    public function testCreationThroughASimpleLinkIsRefused(string $url): void
    {
        $this->createRound(12, 1);

        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(405);
        self::assertNull($this->findRound(12, 2));
        self::assertNull($this->findRound(13, 1));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function creationUrlsProvider(): iterable
    {
        yield 'new round' => ['/referee/bingo/add-round/12'];
        yield 'new season' => ['/referee/bingo/add-season'];
    }

    public function testFillsTheMissingRoundsOfASeason(): void
    {
        $this->createRound(12, 1);
        $this->createRound(12, 4);
        $token = $this->fetchToken('/referee/bingo', 'form[action="/referee/bingo/fill-gaps/12"]');

        $this->client->request('POST', '/referee/bingo/fill-gaps/12', ['token' => $token]);

        self::assertResponseRedirects('/referee/bingo');
        self::assertNotNull($this->findRound(12, 2));
        self::assertNotNull($this->findRound(12, 3));
    }

    public function testFillGapsWithAnInvalidTokenIsRejected(): void
    {
        $this->createRound(12, 1);
        $this->createRound(12, 3);

        $this->client->request('POST', '/referee/bingo/fill-gaps/12', ['token' => 'invalid']);

        self::assertResponseStatusCodeSame(400);
        self::assertNull($this->findRound(12, 2));
    }

    public function testDeletesABingo(): void
    {
        $round = $this->createRound(12, 4, complete: true);
        $roundId = $round->getId();
        $token = $this->fetchToken('/referee/bingo/edit/' . $roundId, '#delete-bingo-modal');

        $this->client->request('POST', '/referee/bingo/delete/' . $roundId, ['token' => $token]);

        self::assertResponseRedirects('/referee/bingo');
        self::assertNull($this->reload(GameRound::class, $roundId));
    }

    public function testDeleteWithAnInvalidTokenIsRejected(): void
    {
        $round = $this->createRound(12, 4);

        $this->client->request('POST', '/referee/bingo/delete/' . $round->getId(), ['token' => 'invalid']);

        self::assertResponseStatusCodeSame(400);
        self::assertNotNull($this->reload(GameRound::class, $round->getId()));
    }

    /**
     * @param array<string, string> $squares values keyed by their path under bingo_form[squares]
     */
    private function submitBingoForm(string $url, int $season, int $round, array $squares): void
    {
        $crawler = $this->client->request('GET', $url);
        $form = $crawler->selectButton('Enregistrer')->form();
        $form->disableValidation();
        $form['bingo_form[season]'] = (string) $season;
        $form['bingo_form[round]'] = (string) $round;
        foreach ($squares as $path => $value) {
            $form['bingo_form[squares]' . $path] = $value;
        }

        $this->client->submit($form);
    }

    private function findRound(int $season, int $number): ?GameRound
    {
        $this->em->clear();

        return $this->em->getRepository(GameRound::class)->findOneBy(['season' => $season, 'number' => $number]);
    }
}

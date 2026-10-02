<?php

declare(strict_types=1);

namespace App\Tests\Controller\Referee;

use App\Tests\Controller\ControllerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The whole /referee area is reserved to referees.
 */
class RefereeAccessTest extends ControllerTestCase
{
    #[DataProvider('refereePagesProvider')]
    public function testAnonymousVisitorIsRedirectedToLogin(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertResponseRedirects('/user/login');
    }

    #[DataProvider('refereePagesProvider')]
    public function testPlayerIsForbidden(string $url): void
    {
        $this->client->loginUser($this->createUser());

        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(403);
    }

    #[DataProvider('refereePagesProvider')]
    public function testRefereeCanOpenThePage(string $url): void
    {
        $this->loginAsReferee();

        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function refereePagesProvider(): iterable
    {
        yield 'dashboard' => ['/referee'];
        yield 'games' => ['/referee/game'];
        yield 'new game' => ['/referee/game/add'];
        yield 'bingos' => ['/referee/bingo'];
        yield 'new bingo' => ['/referee/bingo/add'];
        yield 'teams' => ['/referee/team'];
        yield 'new team' => ['/referee/team/add'];
    }
}

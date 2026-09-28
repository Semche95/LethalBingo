<?php

declare(strict_types=1);

namespace App\Tests\Controller;

class CsrfTokenControllerTest extends ControllerTestCase
{
    public function testReturnsATokenForEveryRequestedId(): void
    {
        $this->loginAsReferee();

        $this->client->request('GET', '/csrf-tokens', ['ids' => ['add-season', 'delete-team']]);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('Cache-Control'));
        $tokens = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(['add-season', 'delete-team'], array_keys($tokens));
        self::assertNotEmpty($tokens['add-season']);
        self::assertNotEmpty($tokens['delete-team']);
    }

    public function testReturnedTokenIsAcceptedByTheAction(): void
    {
        $this->loginAsReferee();
        $this->client->request('GET', '/csrf-tokens', ['ids' => ['add-season']]);
        $token = json_decode((string) $this->client->getResponse()->getContent(), true)['add-season'];

        $this->client->request('POST', '/referee/bingo/add-season', ['token' => $token]);

        self::assertResponseRedirects('/referee/bingo');
    }

    public function testWorksWithoutTokenToKeepTheSessionAlive(): void
    {
        $this->loginAsReferee();

        $this->client->request('GET', '/csrf-tokens');

        self::assertResponseIsSuccessful();
        self::assertSame([], json_decode((string) $this->client->getResponse()->getContent(), true));
    }

    public function testRejectsMalformedIds(): void
    {
        $this->loginAsReferee();

        $this->client->request('GET', '/csrf-tokens', ['ids' => ['Bad Id!']]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testRejectsTooManyIds(): void
    {
        $this->loginAsReferee();

        $this->client->request('GET', '/csrf-tokens', ['ids' => array_map(static fn (int $i): string => 'id-' . $i, range(1, 21))]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testAnonymousVisitorIsRedirectedToTheLoginPage(): void
    {
        $this->client->request('GET', '/csrf-tokens', ['ids' => ['add-season']]);

        self::assertResponseRedirects('/user/login');
    }
}

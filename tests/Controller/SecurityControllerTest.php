<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;

class SecurityControllerTest extends ControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Failed logins are counted on disk: start each test with no attempt left over
        self::getContainer()->get('cache.rate_limiter')->clear();
    }

    public function testLoginPageIsPublic(): void
    {
        $this->client->request('GET', '/user/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_username"]');
    }

    public function testLoggedInRefereeIsRedirectedFromTheLoginPageToTheDashboard(): void
    {
        $this->loginAsReferee();

        $this->client->request('GET', '/user/login');

        self::assertResponseRedirects('/referee');
    }

    public function testLoggedInUserWithoutRefereeRoleIsRedirectedFromTheLoginPageToTheHomePage(): void
    {
        $this->client->loginUser($this->createUser());

        $this->client->request('GET', '/user/login');

        self::assertResponseRedirects('/');
    }

    public function testValidCredentialsLogInAndRedirectToTheRefereeDashboard(): void
    {
        $user = $this->createUser(['ROLE_REFEREE']);

        $this->client->request('GET', '/user/login');
        $this->client->submitForm('Se connecter', [
            '_username' => $user->getUsername(),
            '_password' => self::PASSWORD,
        ]);

        self::assertResponseRedirects('/referee');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testLoginRecordsTheLoginDate(): void
    {
        $user = $this->createUser(['ROLE_REFEREE']);

        $this->client->request('GET', '/user/login');
        $this->client->submitForm('Se connecter', [
            '_username' => $user->getUsername(),
            '_password' => self::PASSWORD,
        ]);

        self::assertNotNull($this->reload(User::class, $user->getId())?->getLastLoginAt());
    }

    public function testInvalidCredentialsShowAnErrorAndKeepTheUsername(): void
    {
        $user = $this->createUser(['ROLE_REFEREE']);

        $this->client->request('GET', '/user/login');
        $this->client->submitForm('Se connecter', [
            '_username' => $user->getUsername(),
            '_password' => 'wrong-password',
        ]);

        self::assertResponseRedirects('/user/login');
        $this->client->followRedirect();
        self::assertSelectorExists('.alert');
        self::assertInputValueSame('_username', $user->getUsername());
    }

    public function testTooManyFailedAttemptsBlockTheLoginEvenWithTheRightPassword(): void
    {
        $user = $this->createUser(['ROLE_REFEREE']);

        for ($attempt = 0; $attempt < 5; ++$attempt) {
            $this->client->request('GET', '/user/login');
            $this->client->submitForm('Se connecter', [
                '_username' => $user->getUsername(),
                '_password' => 'wrong-password',
            ]);
        }

        $this->client->request('GET', '/user/login');
        $this->client->submitForm('Se connecter', [
            '_username' => $user->getUsername(),
            '_password' => self::PASSWORD,
        ]);

        self::assertResponseRedirects('/user/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'Trop de tentatives de connexion échouées');
    }

    public function testTenFailedAttemptsFromTheSameIpBlockEveryUsername(): void
    {
        $user = $this->createUser(['ROLE_REFEREE']);

        for ($attempt = 0; $attempt < 10; ++$attempt) {
            $this->client->request('GET', '/user/login');
            $this->client->submitForm('Se connecter', [
                '_username' => 'unknown-' . $attempt,
                '_password' => 'wrong-password',
            ]);
        }

        $this->client->request('GET', '/user/login');
        $this->client->submitForm('Se connecter', [
            '_username' => $user->getUsername(),
            '_password' => self::PASSWORD,
        ]);

        self::assertResponseRedirects('/user/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'Trop de tentatives de connexion échouées');
    }

    public function testLogoutFormEndsTheSession(): void
    {
        $this->loginAsReferee();

        $crawler = $this->client->request('GET', '/referee');
        $this->client->submit($crawler->filter('form[action="/user/logout"]')->form());
        $this->client->followRedirect();

        $this->client->request('GET', '/referee');
        self::assertResponseRedirects('/user/login');
    }

    public function testLogoutWithoutAValidTokenKeepsTheSession(): void
    {
        $this->loginAsReferee();

        $this->client->request('GET', '/user/logout');
        $this->client->request('POST', '/user/logout', ['token' => 'forged-token']);

        $this->client->request('GET', '/referee');
        self::assertResponseIsSuccessful();
    }
}

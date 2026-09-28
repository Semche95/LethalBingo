<?php

declare(strict_types=1);

namespace App\Tests\Controller;

class SecurityControllerTest extends ControllerTestCase
{
    public function testLoginPageIsPublic(): void
    {
        $this->client->request('GET', '/user/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_username"]');
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

    public function testLogoutEndsTheSession(): void
    {
        $this->loginAsReferee();

        $this->client->request('GET', '/user/logout');
        $this->client->followRedirect();

        $this->client->request('GET', '/referee');
        self::assertResponseRedirects('/user/login');
    }
}

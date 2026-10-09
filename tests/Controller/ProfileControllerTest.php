<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class ProfileControllerTest extends ControllerTestCase
{
    use InteractsWithLiveComponents;

    private const string NEW_PASSWORD = 'correct horse battery staple';

    public function testAnonymousVisitorIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/user/profile');

        self::assertResponseRedirects('/user/login');
    }

    public function testShowsTheProfileWithThePasswordForm(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/user/profile');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.profile-name', (string) $user);
        self::assertSelectorExists('input[name="change_password_form[currentPassword]"]');
    }

    public function testShowsTheShortcutsToARefereeWithThePreviousLogin(): void
    {
        $user = $this->createUser(['ROLE_REFEREE']);
        $user->recordLogin(new DateTimeImmutable('2026-10-01 10:00'))->recordLogin(new DateTimeImmutable('2026-10-02 10:00'));
        $this->em->flush();
        $this->client->loginUser($user);

        $this->client->request('GET', '/user/profile');

        self::assertSelectorExists('a[href="/referee"]');
        self::assertSelectorExists('a[href="/referee/team"]');
        self::assertSelectorTextContains('.card-footer', 'Connexion précédente le 01/10/2026 à 10:00');
    }

    public function testLogsOutTheOtherSessionsAndKeepsTheCurrentOne(): void
    {
        $user = $this->createUser(['ROLE_REFEREE']);
        $this->client->loginUser($user);
        $otherSession = $this->client->getCookieJar()->get('MOCKSESSID');
        $this->client->getCookieJar()->clear();
        $this->client->loginUser($user);

        $token = $this->fetchToken('/user/profile', 'form[action="/user/logout-other-sessions"]');
        $this->client->request('POST', '/user/logout-other-sessions', ['token' => $token]);

        self::assertResponseRedirects('/user/profile');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertFlash('success', 'Les autres sessions ont été déconnectées.');

        $this->client->getCookieJar()->set($otherSession);
        $this->client->request('GET', '/user/profile');
        self::assertResponseRedirects('/user/login');
    }

    public function testRejectsTheLogoutOfOtherSessionsWithoutAValidToken(): void
    {
        $this->loginAsReferee();

        $this->client->request('POST', '/user/logout-other-sessions', ['token' => 'invalid']);

        self::assertResponseStatusCodeSame(400);
    }

    public function testChangesThePasswordWhenTheCurrentOneIsCorrect(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->submitPasswordChange(self::PASSWORD, self::NEW_PASSWORD);

        self::assertResponseRedirects('/user/profile');
        $this->client->request('GET', '/user/profile');
        self::assertFlash('success', 'Votre mot de passe a été modifié avec succès.');
        self::assertTrue($this->isPasswordValid($user, self::NEW_PASSWORD));
    }

    public function testKeepsThePasswordWhenTheCurrentOneIsWrong(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->assertRejected(fn () => $this->submitPasswordChange('wrong-password', self::NEW_PASSWORD));

        self::assertTrue($this->isPasswordValid($user, self::PASSWORD));
    }

    public function testRejectsANewPasswordThatIsTooShort(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->assertRejected(fn () => $this->submitPasswordChange(self::PASSWORD, 'abc'));

        self::assertTrue($this->isPasswordValid($user, self::PASSWORD));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function weakPasswords(): iterable
    {
        yield 'too short' => ['Ab1!xyz'];
        yield 'long but not varied' => ['aaaaaaaaaaaaaaaa'];
        yield 'the current password' => [self::PASSWORD];
    }

    #[DataProvider('weakPasswords')]
    public function testRejectsANewPasswordBreakingARule(string $newPassword): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->assertRejected(fn () => $this->submitPasswordChange(self::PASSWORD, $newPassword));

        self::assertTrue($this->isPasswordValid($user, self::PASSWORD));
    }

    public function testRejectsANewPasswordContainingTheUsername(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->assertRejected(fn () => $this->submitPasswordChange(self::PASSWORD, 'my ' . $user->getUsername() . ' horse battery'));

        self::assertTrue($this->isPasswordValid($user, self::PASSWORD));
    }

    public function testMarksTheRulesMetByTheNewPasswordAsItIsTyped(): void
    {
        $this->client->loginUser($this->createUser());
        $component = $this->createLiveComponent('ChangePasswordForm', client: $this->client);

        $component->set('change_password_form.plainPassword.first', 'abc');
        self::assertCount(2, $component->render()->crawler()->filter('li.text-success'));

        $component->set('change_password_form.plainPassword.first', self::NEW_PASSWORD);
        self::assertCount(4, $component->render()->crawler()->filter('li.text-success'));
    }

    private function submitPasswordChange(string $currentPassword, string $newPassword): void
    {
        $this->createLiveComponent('ChangePasswordForm', client: $this->client)->submitForm([
            'change_password_form' => [
                'currentPassword' => $currentPassword,
                'plainPassword' => ['first' => $newPassword, 'second' => $newPassword],
            ],
        ], 'save');
    }

    private function assertRejected(callable $submit): void
    {
        try {
            $submit();
            self::fail('The password change must be rejected.');
        } catch (UnprocessableEntityHttpException) {
        }
    }

    private function isPasswordValid(User $user, string $password): bool
    {
        $reloaded = $this->reload(User::class, $user->getId());

        return self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($reloaded, $password);
    }
}

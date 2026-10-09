<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class ProfileControllerTest extends ControllerTestCase
{
    use InteractsWithLiveComponents;

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

    public function testChangesThePasswordWhenTheCurrentOneIsCorrect(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->submitPasswordChange(self::PASSWORD, 'new-password');

        self::assertResponseRedirects('/user/profile');
        $this->client->request('GET', '/user/profile');
        self::assertFlash('success', 'Votre mot de passe a été modifié avec succès.');
        self::assertTrue($this->isPasswordValid($user, 'new-password'));
    }

    public function testKeepsThePasswordWhenTheCurrentOneIsWrong(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->assertRejected(fn () => $this->submitPasswordChange('wrong-password', 'new-password'));

        self::assertTrue($this->isPasswordValid($user, self::PASSWORD));
    }

    public function testRejectsANewPasswordThatIsTooShort(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->assertRejected(fn () => $this->submitPasswordChange(self::PASSWORD, 'abc'));

        self::assertTrue($this->isPasswordValid($user, self::PASSWORD));
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

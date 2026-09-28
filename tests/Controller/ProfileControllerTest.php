<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileControllerTest extends ControllerTestCase
{
    public function testAnonymousVisitorIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/user/profile');

        self::assertResponseRedirects('/user/login');
    }

    public function testChangesThePasswordWhenTheCurrentOneIsCorrect(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->submitPasswordChange(self::PASSWORD, 'new-password');

        self::assertResponseRedirects('/user/profile');
        $this->client->followRedirect();
        self::assertFlash('success', 'Votre mot de passe a été modifié avec succès.');
        self::assertTrue($this->isPasswordValid($user, 'new-password'));
    }

    public function testKeepsThePasswordWhenTheCurrentOneIsWrong(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->submitPasswordChange('wrong-password', 'new-password');

        self::assertResponseRedirects('/user/profile');
        $this->client->followRedirect();
        self::assertFlash('danger', 'Le mot de passe actuel est incorrect.');
        self::assertTrue($this->isPasswordValid($user, self::PASSWORD));
    }

    public function testRejectsANewPasswordThatIsTooShort(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->submitPasswordChange(self::PASSWORD, 'abc');

        self::assertResponseStatusCodeSame(422);
        self::assertTrue($this->isPasswordValid($user, self::PASSWORD));
    }

    private function submitPasswordChange(string $currentPassword, string $newPassword): void
    {
        $this->client->request('GET', '/user/profile');
        $this->client->submitForm('Changer le mot de passe', [
            'change_password_form[currentPassword]' => $currentPassword,
            'change_password_form[plainPassword][first]' => $newPassword,
            'change_password_form[plainPassword][second]' => $newPassword,
        ]);
    }

    private function isPasswordValid(User $user, string $password): bool
    {
        $reloaded = $this->reload(User::class, $user->getId());

        return self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($reloaded, $password);
    }
}

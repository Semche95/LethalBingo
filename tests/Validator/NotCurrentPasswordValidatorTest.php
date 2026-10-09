<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\User;
use App\Validator\NotCurrentPassword;
use App\Validator\NotCurrentPasswordValidator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<NotCurrentPasswordValidator>
 */
class NotCurrentPasswordValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotCurrentPasswordValidator
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new User()->setUsername('yippee')->setPassword('hash'));
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher->method('isPasswordValid')->willReturnCallback(fn (User $user, string $password): bool => $password === 'current password');

        return new NotCurrentPasswordValidator($security, $passwordHasher);
    }

    public function testAcceptsANewPassword(): void
    {
        $this->validator->validate('correct horse battery staple', new NotCurrentPassword());

        $this->assertNoViolation();
    }

    public function testRejectsTheCurrentPassword(): void
    {
        $constraint = new NotCurrentPassword();
        $this->validator->validate('current password', $constraint);

        $this->buildViolation($constraint->message)->assertRaised();
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\User;
use App\Validator\NotContainingUsername;
use App\Validator\NotContainingUsernameValidator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<NotContainingUsernameValidator>
 */
class NotContainingUsernameValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotContainingUsernameValidator
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new User()->setUsername('Yippee'));

        return new NotContainingUsernameValidator($security);
    }

    public function testAcceptsAPasswordWithoutTheUsername(): void
    {
        $this->validator->validate('correct horse battery staple', new NotContainingUsername());

        $this->assertNoViolation();
    }

    public function testRejectsAPasswordContainingTheUsernameWhateverTheCase(): void
    {
        $constraint = new NotContainingUsername();
        $this->validator->validate('my-YIPPEE-password-2026', $constraint);

        $this->buildViolation($constraint->message)->assertRaised();
    }
}

<?php

declare(strict_types=1);

namespace App\Validator;

use SensitiveParameter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NotCurrentPasswordValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Security $security,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function validate(#[SensitiveParameter] mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotCurrentPassword) {
            throw new UnexpectedTypeException($constraint, NotCurrentPassword::class);
        }

        $user = $this->security->getUser();
        if (!\is_string($value) || $value === '' || !$user instanceof PasswordAuthenticatedUserInterface) {
            return;
        }

        if (!$this->passwordHasher->isPasswordValid($user, $value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->addViolation();
    }
}

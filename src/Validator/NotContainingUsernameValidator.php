<?php

declare(strict_types=1);

namespace App\Validator;

use SensitiveParameter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NotContainingUsernameValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function validate(#[SensitiveParameter] mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotContainingUsername) {
            throw new UnexpectedTypeException($constraint, NotContainingUsername::class);
        }

        $username = $this->security->getUser()?->getUserIdentifier();
        if (!\is_string($value) || $username === null) {
            return;
        }

        if (!str_contains(mb_strtolower($value), mb_strtolower($username))) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->addViolation();
    }
}

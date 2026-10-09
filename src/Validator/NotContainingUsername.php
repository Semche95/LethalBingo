<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * The password must not contain the username of the logged-in user, whatever the case.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class NotContainingUsername extends Constraint
{
    public string $message = 'Ne contient pas votre identifiant';
}

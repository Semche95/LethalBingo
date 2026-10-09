<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * The new password must differ from the current password of the logged-in user.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class NotCurrentPassword extends Constraint
{
    public string $message = 'Différent du mot de passe actuel';
}

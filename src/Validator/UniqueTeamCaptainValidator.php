<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\TeamMember;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueTeamCaptainValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var UniqueTeamCaptain $constraint */
        if (!$value instanceof Collection) {
            return;
        }

        $captainCount = $value->filter(fn (TeamMember $member) => $member->isCaptain())->count();
        if ($captainCount <= 1) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->addViolation();
    }
}

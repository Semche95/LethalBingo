<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\TeamMember;
use App\Validator\UniqueTeamCaptain;
use App\Validator\UniqueTeamCaptainValidator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniqueTeamCaptainValidatorTest extends TestCase
{
    private UniqueTeamCaptainValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UniqueTeamCaptainValidator();
    }

    public function testNoViolationWithZeroCaptains(): void
    {
        $members = new ArrayCollection([
            $this->createMember(false),
            $this->createMember(false),
        ]);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $this->validator->validateInContext($members, new UniqueTeamCaptain(), $context);
    }

    public function testNoViolationWithExactlyOneCaptain(): void
    {
        $members = new ArrayCollection([
            $this->createMember(true),
            $this->createMember(false),
            $this->createMember(false),
        ]);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $this->validator->validateInContext($members, new UniqueTeamCaptain(), $context);
    }

    public function testViolationWithTwoCaptains(): void
    {
        $members = new ArrayCollection([
            $this->createMember(true),
            $this->createMember(true),
            $this->createMember(false),
        ]);

        $constraint = new UniqueTeamCaptain();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation')->willReturnSelf();

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);

        $this->validator->validateInContext($members, $constraint, $context);
    }

    public function testViolationWithThreeCaptains(): void
    {
        $members = new ArrayCollection([
            $this->createMember(true),
            $this->createMember(true),
            $this->createMember(true),
        ]);

        $constraint = new UniqueTeamCaptain();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation')->willReturnSelf();

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);

        $this->validator->validateInContext($members, $constraint, $context);
    }

    public function testNonCollectionValueIsSkipped(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $this->validator->validateInContext('not a collection', new UniqueTeamCaptain(), $context);
    }

    public function testNullValueIsSkipped(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $this->validator->validateInContext(null, new UniqueTeamCaptain(), $context);
    }

    public function testEmptyCollectionIsSkipped(): void
    {
        $members = new ArrayCollection([]);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $this->validator->validateInContext($members, new UniqueTeamCaptain(), $context);
    }

    private function createMember(bool $isCaptain): TeamMember
    {
        $member = new TeamMember();
        $member->setCaptain($isCaptain);

        return $member;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\TeamMember;
use PHPUnit\Framework\TestCase;

class TeamMemberTest extends TestCase
{
    public function testDefaultCaptainIsFalse(): void
    {
        $member = new TeamMember();

        $this->assertFalse($member->isCaptain());
    }

    public function testSetCaptain(): void
    {
        $member = new TeamMember();
        $member->setCaptain(true);

        $this->assertTrue($member->isCaptain());
    }

    public function testGetNameAndSetName(): void
    {
        $member = new TeamMember();
        $member->setName('Alice');

        $this->assertSame('Alice', $member->getName());
    }

    public function testToStringReturnsName(): void
    {
        $member = new TeamMember();
        $member->setName('Alice');

        $this->assertSame('Alice', (string) $member);
    }

    public function testToStringReturnsEmptyStringWhenNameIsNotSet(): void
    {
        $member = new TeamMember();

        $this->assertSame('', (string) $member);
    }

    public function testNameDefaultsToEmptyString(): void
    {
        $member = new TeamMember();

        $this->assertSame('', $member->getName());
    }
}

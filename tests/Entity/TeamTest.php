<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Team;
use App\Entity\TeamMember;
use PHPUnit\Framework\TestCase;

class TeamTest extends TestCase
{
    public function testGetNameAndSetName(): void
    {
        $team = new Team();
        $team->setName('Alpha Team');

        $this->assertSame('Alpha Team', $team->getName());
    }

    public function testToStringReturnsNameOnly(): void
    {
        $team = new Team();
        $team->setName('Alpha Team');

        $this->assertSame('Alpha Team', (string) $team);
    }

    public function testToStringReturnsEmptyStringWhenNameIsNull(): void
    {
        $team = new Team();

        $this->assertSame('', (string) $team);
    }

    public function testAddMemberSetsTeamReference(): void
    {
        $team = new Team();
        $team->setName('Test');
        $member = new TeamMember();
        $member->setName('Alice');

        $team->addMember($member);

        $this->assertCount(1, $team->getMembers());
        $this->assertSame($team, $member->getTeam());
    }

    public function testAddMemberDoesNotDuplicate(): void
    {
        $team = new Team();
        $team->setName('Test');
        $member = new TeamMember();
        $member->setName('Alice');

        $team->addMember($member);
        $team->addMember($member);

        $this->assertCount(1, $team->getMembers());
    }

    public function testRemoveMemberRemovesItFromTheTeam(): void
    {
        $team = new Team();
        $team->setName('Test');
        $member = new TeamMember();
        $member->setName('Alice');

        $team->addMember($member);
        $team->removeMember($member);

        $this->assertCount(0, $team->getMembers());
        $this->assertFalse($team->getMembers()->contains($member));
    }

    public function testToStringWithMembers(): void
    {
        $team = new Team();
        $team->setName('Alpha');

        $member1 = new TeamMember();
        $member1->setName('Alice');
        $team->addMember($member1);

        $member2 = new TeamMember();
        $member2->setName('Bob');
        $team->addMember($member2);

        $result = (string) $team;

        $this->assertStringStartsWith('Alpha (', $result);
        $this->assertStringContainsString('Alice', $result);
        $this->assertStringContainsString('Bob', $result);
    }

    public function testGetIdReturnsNullWhenNew(): void
    {
        $team = new Team();

        $this->assertNull($team->getId());
    }
}

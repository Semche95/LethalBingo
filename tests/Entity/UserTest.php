<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetterAndSetters(): void
    {
        $user = new User();

        // Test username
        $user->setUsername('testuser');
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('testuser', $user->getUserIdentifier());

        // Test password
        $user->setPassword('password123');
        $this->assertEquals('password123', $user->getPassword());

        // Test roles
        $user->setRoles(['ROLE_ADMIN']);
        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles); // Default role should be added
        $this->assertCount(2, $roles); // Should be unique

        // Test toString
        $this->assertEquals('testuser', (string) $user);
    }

    public function testEmptyUser(): void
    {
        $user = new User();

        // Test default values
        $this->assertNull($user->getId());
        $this->assertEquals('', (string) $user);
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }
}

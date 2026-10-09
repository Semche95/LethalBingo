<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface, Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $username;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private string $password;

    /**
     * Bumped to log out every other session and remember-me cookie of the user
     * (compared by isEqualTo() and signed into the remember-me cookie).
     */
    #[ORM\Column(options: ['default' => 0])]
    private int $sessionVersion = 0;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastLoginAt = null;

    /** Login before the current one, shown on the profile to spot an unknown login */
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $previousLoginAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @return list<string>
     *
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getSessionVersion(): int
    {
        return $this->sessionVersion;
    }

    public function invalidateOtherSessions(): static
    {
        ++$this->sessionVersion;

        return $this;
    }

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getPreviousLoginAt(): ?DateTimeImmutable
    {
        return $this->previousLoginAt;
    }

    public function recordLogin(DateTimeImmutable $at): static
    {
        $this->previousLoginAt = $this->lastLoginAt;
        $this->lastLoginAt = $at;

        return $this;
    }

    /**
     * Same checks as Symfony's default comparison (password, roles, identifier),
     * plus the session version: a session opened before invalidateOtherSessions()
     * no longer matches the user reloaded from the database and is logged out.
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        $roles = $this->getRoles();
        $otherRoles = $user->getRoles();
        sort($roles);
        sort($otherRoles);

        return $this->password === $user->getPassword()
            && $this->username === $user->getUserIdentifier()
            && $roles === $otherRoles
            && $this->sessionVersion === $user->getSessionVersion();
    }

    public function __toString(): string
    {
        return $this->username ?? '';
    }
}

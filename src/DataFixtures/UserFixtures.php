<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixture for loading users data.
 */
class UserFixtures extends Fixture
{
    /**
     * @param UserPasswordHasherInterface $passwordHasher Service to hash user passwords
     * @param string                      $password       Password shared by every fixture user, set in .env.local
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire(env: 'FIXTURES_PASSWORD'), SensitiveParameter]
        private readonly string $password,
    ) {
    }

    /**
     * Load users data.
     */
    public function load(ObjectManager $manager): void
    {
        if ($this->password === '') {
            throw new LogicException('Define FIXTURES_PASSWORD in .env.local before loading the fixtures.');
        }

        $users = [
            'bracken' => ['ROLE_ADMIN'],
            'yippee' => ['ROLE_REFEREE'],
        ];
        foreach ($users as $username => $roles) {
            $user = new User();
            $user->setUsername($username)
                ->setRoles($roles)
                ->setPassword($this->passwordHasher->hashPassword($user, $this->password));
            $manager->persist($user);
        }
        $manager->flush();
    }
}

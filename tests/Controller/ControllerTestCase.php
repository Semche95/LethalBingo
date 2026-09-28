<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Runs every test inside a transaction rolled back on tearDown, so the test
 * database stays empty whatever the test writes. The kernel is never rebooted
 * between requests to keep that single connection (and its transaction).
 */
abstract class ControllerTestCase extends WebTestCase
{
    protected const string PASSWORD = 'secret-password';

    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->em->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        parent::tearDown();
    }

    /**
     * @param list<string> $roles
     */
    protected function createUser(array $roles = []): User
    {
        $user = new User()
            ->setUsername('user-' . bin2hex(random_bytes(6)))
            ->setRoles($roles);
        $user->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD));

        return $this->save($user);
    }

    protected function loginAsReferee(): User
    {
        $user = $this->createUser(['ROLE_REFEREE']);
        $this->client->loginUser($user);

        return $user;
    }

    protected function createTeam(): Team
    {
        $team = new Team()
            ->setName('Team ' . bin2hex(random_bytes(6)))
            ->addMember(new TeamMember()->setName('Captain')->setCaptain(true));

        return $this->save($team);
    }

    protected function createRound(int $season, int $number, bool $complete = false): GameRound
    {
        $round = new GameRound()
            ->setSeason($season)
            ->setNumber($number);
        $this->em->persist($round);

        if ($complete) {
            $category = $this->em->getRepository(BingoSquareCategory::class)->findOneBy([]);
            for ($row = 1; $row <= GameRound::MAX_ROWS; $row++) {
                for ($col = 1; $col <= GameRound::MAX_COLS; $col++) {
                    $square = new BingoSquare()
                        ->setRow($row)
                        ->setCol($col)
                        ->setLabel(sprintf('Square %d-%d', $row, $col))
                        ->setVisible(true)
                        ->setCategory($category);
                    $round->addBingoSquare($square);
                    $this->em->persist($square);
                }
            }
        }

        return $this->save($round);
    }

    protected function createGame(?GameRound $round = null): Game
    {
        $game = new Game()
            ->setTeam($this->createTeam())
            ->setRound($round ?? $this->createRound(1, 1, complete: true));

        return $this->save($game);
    }

    /**
     * Opens the page holding a confirmation form and returns its CSRF token.
     */
    protected function fetchToken(string $url, string $formSelector): string
    {
        $crawler = $this->client->request('GET', $url);

        return (string) $crawler->filter($formSelector . ' input[name="token"]')->attr('value');
    }

    protected static function assertFlash(string $type, string $message): void
    {
        self::assertSelectorTextContains('.toast-outline-' . $type . ' .toast-body', $message);
    }

    /**
     * Reloads an entity from the database, bypassing the identity map shared
     * with the requests, or returns null once it has been deleted.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    protected function reload(string $class, int $id): ?object
    {
        $this->em->clear();

        return $this->em->find($class, $id);
    }

    /**
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    private function save(object $entity): object
    {
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }
}

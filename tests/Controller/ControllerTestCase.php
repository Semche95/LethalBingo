<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\BingoSquareRevealed;
use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Profiler\Profile;
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
            for ($row = 1; $row <= GameRound::MAX_ROWS; ++$row) {
                for ($col = 1; $col <= GameRound::MAX_COLS; ++$col) {
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
     * Game in progress with two revealed squares (A1, B2) and two validated
     * squares (A3, B2).
     */
    protected function createPlayedGame(): Game
    {
        $game = $this->createGame()->setStartedAt(new DateTimeImmutable('-5 minutes'));
        $this->revealSquare($game, 1, 1);
        $this->revealSquare($game, 2, 2);
        $this->validateSquare($game, 1, 3);
        $this->validateSquare($game, 2, 2);

        return $game;
    }

    protected function revealSquare(Game $game, int $row, int $col): void
    {
        $this->save(new BingoSquareRevealed()
            ->setGame($game)
            ->setBingoSquare($this->findSquare($game, $row, $col))
            ->setRevealedAt(new DateTimeImmutable()));
    }

    protected function validateSquare(Game $game, int $row, int $col): void
    {
        $this->save(new BingoSquareValidated()
            ->setGame($game)
            ->setBingoSquare($this->findSquare($game, $row, $col))
            ->setValidatedAt(new DateTimeImmutable()));
    }

    /**
     * Starts counting the SQL queries of the next request. The identity map is
     * emptied first, so that the entities created by the test are loaded by
     * the request itself as they would be in production.
     */
    protected function countNextRequestQueries(): void
    {
        $this->em->clear();
        self::getContainer()->get('doctrine.debug_data_holder')->reset();
        $this->client->enableProfiler();
    }

    protected function assertQueryCountAtMost(int $max): void
    {
        $profile = $this->client->getProfile();
        self::assertInstanceOf(Profile::class, $profile, 'The profiler must be enabled before the request.');
        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);
        $queries = array_column($collector->getQueries()['default'] ?? [], 'sql');

        self::assertLessThanOrEqual(
            $max,
            $collector->getQueryCount(),
            sprintf("Too many SQL queries:\n%s", implode("\n", $queries)),
        );
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

    private function findSquare(Game $game, int $row, int $col): BingoSquare
    {
        return $this->em->getRepository(BingoSquare::class)->findOneBy(['round' => $game->getRound(), 'row' => $row, 'col' => $col]);
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

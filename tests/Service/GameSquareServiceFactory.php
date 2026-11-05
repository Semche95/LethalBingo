<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Repository\BingoSquareRepository;
use App\Repository\BingoSquareRevealedRepository;
use App\Repository\BingoSquareValidatedRepository;
use App\Service\GameService;
use App\Service\GameSquareService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Mercure\HubInterface;

/**
 * Builds GameSquareService for the GameSquareService test cases.
 */
trait GameSquareServiceFactory
{
    /**
     * Builds the service, replacing every collaborator not given by a stub.
     */
    private function createGameSquareService(
        ?EntityManagerInterface $em = null,
        ?BingoSquareRevealedRepository $revealedRepository = null,
        ?BingoSquareValidatedRepository $validatedRepository = null,
        ?HubInterface $hub = null,
        ?BingoSquareRepository $squareRepository = null,
        ?LockFactory $lockFactory = null,
    ): GameSquareService {
        $em ??= $this->createStub(EntityManagerInterface::class);

        return new GameSquareService(
            $em,
            $squareRepository ?? $this->createStub(BingoSquareRepository::class),
            $revealedRepository ?? $this->createStub(BingoSquareRevealedRepository::class),
            $validatedRepository ?? $this->createStub(BingoSquareValidatedRepository::class),
            new GameService($em, $hub ?? $this->createStub(HubInterface::class)),
            $lockFactory ?? new LockFactory(new InMemoryStore()),
        );
    }
}

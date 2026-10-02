<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GameDto;
use App\Entity\Game;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

readonly class GameService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HubInterface $hub,
    ) {
    }

    public function refreshGameFrame(Game $game, RefreshEvent $event = RefreshEvent::GameFrame): float
    {
        $serverTime = microtime(true);

        $this->hub->publish(new Update(
            'game/' . $game->getSlug(),
            json_encode([
                'event' => $event->value,
                'serverTime' => $serverTime,
            ], JSON_THROW_ON_ERROR),
        ));

        return $serverTime;
    }

    public function entityToDto(Game $game): GameDto
    {
        return new GameDto($game->getTeam(), $game->getRound());
    }

    public function create(GameDto $gameDto): Game
    {
        $game = new Game()
            ->setTeam($gameDto->team)
            ->setRound($gameDto->round);
        $this->em->persist($game);
        $this->em->flush();

        return $game;
    }

    public function update(GameDto $gameDto, Game $game): void
    {
        $game
            ->setTeam($gameDto->team)
            ->setRound($gameDto->round);
        $this->em->persist($game);
        $this->em->flush();
    }

    public function start(Game $game): void
    {
        $game
            ->setStartedAt(new DateTimeImmutable())
            ->setFinishedAt(null)
            ->setIsPaused(false)
            ->setTotalPausedSeconds(0)
            ->setManualAdjustmentSeconds(0)
            ->setLastPausedAt(null);
        $this->em->persist($game);
        $this->em->flush();
        $this->refreshGameFrame($game);
    }

    public function stop(Game $game): void
    {
        // stop() only ever modifies finishedAt; startedAt is never touched here.
        $game->setFinishedAt(new DateTimeImmutable());
        $this->em->persist($game);
        $this->em->flush();
        $this->refreshGameFrame($game);
    }

    public function reset(Game $game): void
    {
        $this->em->wrapInTransaction(function ($em) use ($game): void {
            $game
                ->setStartedAt(null)
                ->setFinishedAt(null)
                ->setIsPaused(false)
                ->setTotalPausedSeconds(0)
                ->setManualAdjustmentSeconds(0)
                ->setLastPausedAt(null);
            $game->getBingoSquareRevealed()->clear();
            foreach ($game->getBingoSquareValidated() as $square) {
                $em->remove($square);
            }
            $em->persist($game);
        });
        $this->refreshGameFrame($game);
    }
}

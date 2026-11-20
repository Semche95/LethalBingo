<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GameDto;
use App\Entity\Game;
use App\Entity\PreviousRoundStatus;
use App\Entity\TimerMode;
use App\Repository\GameRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

readonly class GameService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HubInterface $hub,
        private GameRepository $gameRepository,
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
        return new GameDto($game->getTeam(), $game->getRound(), !$game->isStopwatch(), game: $game)
            ->setCountdownDurationSeconds($game->getCountdownDurationSeconds());
    }

    public function create(GameDto $gameDto): Game
    {
        $game = new Game();
        $this->applyDto($gameDto, $game);
        $this->em->persist($game);
        $this->em->flush();

        return $game;
    }

    public function update(GameDto $gameDto, Game $game): void
    {
        $this->applyDto($gameDto, $game);
        $this->em->persist($game);
        $this->em->flush();
        // Open pages show the timer mode and duration before the start.
        $this->refreshGameFrame($game);
    }

    // The countdown duration is kept as is when the game has no countdown.
    private function applyDto(GameDto $gameDto, Game $game): void
    {
        $game
            ->setTeam($gameDto->team)
            ->setRound($gameDto->round)
            ->setTimerMode($gameDto->hasCountdown ? TimerMode::Countdown : TimerMode::Stopwatch);

        if ($gameDto->hasCountdown) {
            $game->setCountdownDurationSeconds($gameDto->getCountdownDurationSeconds());
        }
    }

    /**
     * Why the team has not finished its game of the previous round of the
     * season; null when it has, or for the first round. Only a warning: it
     * never prevents the start (docs/GAME.md section 2).
     */
    public function previousRoundStatus(Game $game): ?PreviousRoundStatus
    {
        if ($game->getRound()->getNumber() <= 1) {
            return null;
        }

        $previousGames = $this->gameRepository->findPreviousRoundGames($game);
        if ($previousGames === []) {
            return PreviousRoundStatus::NoGame;
        }

        $status = PreviousRoundStatus::NotStarted;
        foreach ($previousGames as $previousGame) {
            if ($previousGame->isFinished()) {
                return null;
            }

            if ($previousGame->isStarted()) {
                $status = PreviousRoundStatus::InProgress;
            }
        }

        return $status;
    }

    public function start(Game $game, DateTimeImmutable $startedAt, ?DateTimeImmutable $countdownZeroAt): void
    {
        if ($game->isReadOnly()) {
            return;
        }

        $game->resetTimer($startedAt, $countdownZeroAt);
        $this->em->persist($game);
        $this->em->flush();
        $this->refreshGameFrame($game);
    }

    public function stop(Game $game, DateTimeImmutable $finishedAt): void
    {
        if (!$game->isInProgress()) {
            return;
        }

        // stop() only ever modifies finishedAt; startedAt is never touched here.
        $game->setFinishedAt($finishedAt);
        $this->em->persist($game);
        $this->em->flush();
        $this->refreshGameFrame($game);
    }

    public function reset(Game $game): void
    {
        if (!$game->isReadOnly()) {
            return;
        }

        $this->em->wrapInTransaction(function ($em) use ($game): void {
            $game->resetTimer(null);
            foreach ([...$game->getBingoSquareRevealed(), ...$game->getBingoSquareValidated(), ...$game->getSquareActions(), ...$game->getHighlights(), ...$game->getRuleValues()] as $entity) {
                $em->remove($entity);
            }
            $game->getBingoSquareRevealed()->clear();
            $game->getBingoSquareValidated()->clear();
            $game->getSquareActions()->clear();
            $game->getHighlights()->clear();
            $game->getRuleValues()->clear();
            $em->persist($game);
        });
        $this->refreshGameFrame($game);
    }
}

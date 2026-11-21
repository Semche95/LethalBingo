<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * For an entity whose games lock it once one of them is started (finished included).
 * Loads the games: never call it once per row of a list.
 *
 * @property Collection<int, Game> $games
 */
trait HasStartedGameTrait
{
    public function hasStartedGame(): bool
    {
        return $this->games->exists(static fn (int $key, Game $game): bool => $game->isReadOnly());
    }
}

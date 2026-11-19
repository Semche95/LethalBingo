<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Dto\GameScore;
use App\Entity\Game;
use App\Service\GameScoreService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;

/**
 * Points of a game rule by rule, on the referee's game page; the values of
 * the free rules are entered once the game is stopped.
 */
#[AsLiveComponent]
#[IsGranted('ROLE_REFEREE')]
final class GamePoints
{
    use DefaultActionTrait;

    #[LiveProp]
    public Game $game;

    /**
     * Values of the free rules as typed by the referee, by rule id.
     *
     * @var array<int, int|string|null>
     */
    #[LiveProp(writable: true)]
    public array $ruleValues = [];

    public function __construct(
        private readonly GameScoreService $gameScoreService,
        private readonly LiveResponder $liveResponder,
    ) {
    }

    public function mount(Game $game): void
    {
        $this->game = $game;
        $this->ruleValues = $this->gameScoreService->freeValues($game);
    }

    public function getScore(): GameScore
    {
        return $this->gameScoreService->compute($this->game, $this->typedValues());
    }

    #[LiveAction]
    public function saveRuleValues(): void
    {
        // A game reset meanwhile saves nothing: no confirmation then.
        if (!$this->gameScoreService->saveFreeValues($this->game, $this->typedValues())) {
            return;
        }
        $this->liveResponder->dispatchBrowserEvent('points:saved', [
            'flash' => ['type' => 'success', 'message' => 'Valeurs enregistrées'],
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function typedValues(): array
    {
        return array_map(intval(...), $this->ruleValues);
    }
}

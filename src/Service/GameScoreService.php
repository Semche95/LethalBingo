<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GameScore;
use App\Dto\RuleScore;
use App\Entity\ComputedMeasure;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\GameRuleValue;
use App\Entity\Rule;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Points of a game from the rules of its round: each rule earns its value
 * times its coefficient. Computed on every call, so a changed coefficient
 * applies to the games already played.
 */
readonly class GameScoreService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @param array<int, int>|null $freeValues values of the free rules by rule id, as
     *                                         typed by the referee; the saved ones when null
     */
    public function compute(Game $game, ?array $freeValues = null): GameScore
    {
        $validated = $this->validatedGrid($game);
        [$fourSquareLines, $fiveSquareLines] = $this->countLines($validated);
        $freeValues ??= $this->freeValues($game);

        $rules = [];
        $total = 0;
        foreach ($game->getRound()->getRules() as $rule) {
            $value = match ($rule->getComputedMeasure()) {
                ComputedMeasure::ValidatedSquares => $game->getBingoSquareValidated()->count(),
                ComputedMeasure::FourSquareLines => $fourSquareLines,
                ComputedMeasure::FiveSquareLines => $fiveSquareLines,
                null => $freeValues[$rule->getId()] ?? 0,
            };
            $points = $value * $rule->getCoefficient();
            $rules[] = new RuleScore($rule, $value, $points);
            $total += $points;
        }

        return new GameScore($rules, $total);
    }

    /**
     * Values of the free rules of the game, by rule id; a rule without a
     * saved value is 0.
     *
     * @return array<int, int>
     */
    public function freeValues(Game $game): array
    {
        $values = [];
        foreach ($game->getRound()->getRules() as $rule) {
            if (!$rule->isComputed()) {
                $values[(int) $rule->getId()] = 0;
            }
        }
        foreach ($game->getRuleValues() as $ruleValue) {
            $values[(int) $ruleValue->getRule()->getId()] = $ruleValue->getValue();
        }

        return $values;
    }

    /**
     * Saves the values entered by the referee for the free rules of the
     * round. Ignored until the game is stopped; values given for any other
     * rule are ignored.
     *
     * @param array<int, int> $values values by rule id
     *
     * @return bool false when the game is not stopped and nothing is saved
     */
    public function saveFreeValues(Game $game, array $values): bool
    {
        if (!$game->isFinished()) {
            return false;
        }

        $saved = [];
        foreach ($game->getRuleValues() as $ruleValue) {
            $saved[(int) $ruleValue->getRule()->getId()] = $ruleValue;
        }

        foreach ($game->getRound()->getRules() as $rule) {
            $ruleId = (int) $rule->getId();
            if ($rule->isComputed() || !array_key_exists($ruleId, $values)) {
                continue;
            }
            $ruleValue = $saved[$ruleId] ?? $this->createRuleValue($game, $rule);
            $ruleValue->setValue($values[$ruleId]);
        }

        $this->em->flush();

        return true;
    }

    private function createRuleValue(Game $game, Rule $rule): GameRuleValue
    {
        $ruleValue = new GameRuleValue($game, $rule);
        $game->getRuleValues()->add($ruleValue);
        $this->em->persist($ruleValue);

        return $ruleValue;
    }

    /**
     * Validated squares of the game, by row then column.
     *
     * @return array<int, array<int, true>>
     */
    private function validatedGrid(Game $game): array
    {
        $grid = [];
        foreach ($game->getBingoSquareValidated() as $validated) {
            $square = $validated->getBingoSquare();
            $grid[$square->getRow()][$square->getCol()] = true;
        }

        return $grid;
    }

    /**
     * Lines of 4 and of 5 validated squares among the rows, the columns and
     * the two diagonals. A line of 4 is made of 4 contiguous squares; a full
     * line only counts as a line of 5.
     *
     * @param array<int, array<int, true>> $validated
     *
     * @return array{int, int} lines of 4, lines of 5
     */
    private function countLines(array $validated): array
    {
        $four = 0;
        $five = 0;
        foreach ($this->lines() as $line) {
            $cells = array_map(static fn (array $cell): bool => isset($validated[$cell[0]][$cell[1]]), $line);
            if (!in_array(false, $cells, true)) {
                ++$five;
            } elseif (!in_array(false, array_slice($cells, 0, 4), true) || !in_array(false, array_slice($cells, 1), true)) {
                ++$four;
            }
        }

        return [$four, $five];
    }

    /**
     * The 12 lines of the square grid, each as its squares [row, col] in order.
     *
     * @return list<list<array{int, int}>>
     */
    private function lines(): array
    {
        $size = GameRound::MAX_ROWS;
        $positions = range(1, $size);
        $lines = [];
        foreach ($positions as $i) {
            $lines[] = array_map(static fn (int $j): array => [$i, $j], $positions);
            $lines[] = array_map(static fn (int $j): array => [$j, $i], $positions);
        }
        $lines[] = array_map(static fn (int $i): array => [$i, $i], $positions);
        $lines[] = array_map(static fn (int $i): array => [$i, $size + 1 - $i], $positions);

        return $lines;
    }
}

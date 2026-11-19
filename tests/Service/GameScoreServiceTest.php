<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareValidated;
use App\Entity\ComputedMeasure;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\GameRuleValue;
use App\Entity\Rule;
use App\Service\GameScoreService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class GameScoreServiceTest extends TestCase
{
    private function createService(?EntityManagerInterface $em = null): GameScoreService
    {
        return new GameScoreService($em ?? $this->createStub(EntityManagerInterface::class));
    }

    private function rule(int $id, ?ComputedMeasure $computedMeasure, int $coefficient): Rule
    {
        $rule = new Rule()
            ->setComputedMeasure($computedMeasure)
            ->setMeasure($computedMeasure === null ? 'Mesure ' . $id : null)
            ->setCoefficient($coefficient);
        new ReflectionProperty(Rule::class, 'id')->setValue($rule, $id);

        return $rule;
    }

    /**
     * @param list<array{int, int}> $validatedSquares [row, col] of each validated square
     */
    private function game(array $validatedSquares, Rule ...$rules): Game
    {
        $round = new GameRound();
        foreach ($rules as $rule) {
            $round->addRule($rule);
        }
        $game = new Game()->setRound($round);
        foreach ($validatedSquares as [$row, $col]) {
            $game->addBingoSquareValidated(new BingoSquareValidated()->setBingoSquare(new BingoSquare()->setRow($row)->setCol($col)));
        }

        return $game;
    }

    /**
     * @param list<array{int, int}> $squares
     */
    #[DataProvider('linesProvider')]
    public function testComputeCountsTheLines(array $squares, int $fourSquareLines, int $fiveSquareLines): void
    {
        $game = $this->game($squares, $this->rule(1, ComputedMeasure::FourSquareLines, 1), $this->rule(2, ComputedMeasure::FiveSquareLines, 1));

        $score = $this->createService()->compute($game);

        $this->assertSame($fourSquareLines, $score->rules[0]->value);
        $this->assertSame($fiveSquareLines, $score->rules[1]->value);
    }

    /**
     * @return iterable<string, array{list<array{int, int}>, int, int}>
     */
    public static function linesProvider(): iterable
    {
        yield 'no square' => [[], 0, 0];
        yield 'full row' => [[[2, 1], [2, 2], [2, 3], [2, 4], [2, 5]], 0, 1];
        yield 'full column' => [[[1, 4], [2, 4], [3, 4], [4, 4], [5, 4]], 0, 1];
        yield 'full diagonal' => [[[1, 1], [2, 2], [3, 3], [4, 4], [5, 5]], 0, 1];
        yield 'full anti-diagonal' => [[[1, 5], [2, 4], [3, 3], [4, 2], [5, 1]], 0, 1];
        yield 'row of 4 from the first square' => [[[3, 1], [3, 2], [3, 3], [3, 4]], 1, 0];
        yield 'row of 4 up to the last square' => [[[3, 2], [3, 3], [3, 4], [3, 5]], 1, 0];
        yield 'diagonal of 4' => [[[2, 2], [3, 3], [4, 4], [5, 5]], 1, 0];
        yield 'short diagonal A2-D5' => [[[1, 2], [2, 3], [3, 4], [4, 5]], 1, 0];
        yield 'short diagonal B1-E4' => [[[2, 1], [3, 2], [4, 3], [5, 4]], 1, 0];
        yield 'short anti-diagonal A4-D1' => [[[1, 4], [2, 3], [3, 2], [4, 1]], 1, 0];
        yield 'short anti-diagonal B5-E2' => [[[2, 5], [3, 4], [4, 3], [5, 2]], 1, 0];
        yield 'short diagonal of 3' => [[[1, 2], [2, 3], [3, 4]], 0, 0];
        yield 'short diagonal of 3 up to its last square' => [[[2, 3], [3, 4], [4, 5]], 0, 0];
        yield '4 squares not contiguous' => [[[1, 1], [1, 2], [1, 4], [1, 5]], 0, 0];
        yield 'row of 3' => [[[1, 1], [1, 2], [1, 3]], 0, 0];
        yield 'full row and column of 4 crossing it' => [[[1, 1], [1, 2], [1, 3], [1, 4], [1, 5], [2, 1], [3, 1], [4, 1]], 1, 1];
    }

    public function testComputeMultipliesEachValueByItsCoefficientAndSumsThePoints(): void
    {
        $validatedSquares = $this->rule(1, ComputedMeasure::ValidatedSquares, 100);
        $freeRule = $this->rule(2, null, -50);
        $game = $this->game([[1, 1], [3, 3]], $validatedSquares, $freeRule);
        $game->getRuleValues()->add(new GameRuleValue($game, $freeRule)->setValue(3));

        $score = $this->createService()->compute($game);

        $this->assertSame($validatedSquares, $score->rules[0]->rule);
        $this->assertSame(2, $score->rules[0]->value);
        $this->assertSame(200, $score->rules[0]->points);
        $this->assertSame(3, $score->rules[1]->value);
        $this->assertSame(-150, $score->rules[1]->points);
        $this->assertSame(50, $score->total);
    }

    public function testComputeCountsAFreeRuleWithoutValueAsZero(): void
    {
        $score = $this->createService()->compute($this->game([], $this->rule(1, null, 100)));

        $this->assertSame(0, $score->rules[0]->value);
        $this->assertSame(0, $score->total);
    }

    public function testSaveFreeValuesCreatesThenUpdatesTheValues(): void
    {
        $freeRule = $this->rule(1, null, 10);
        $game = $this->game([], $freeRule)->setStartedAt(new DateTimeImmutable())->setFinishedAt(new DateTimeImmutable());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(GameRuleValue::class));
        $em->expects($this->exactly(2))->method('flush');
        $service = $this->createService($em);

        $service->saveFreeValues($game, [1 => 4]);
        $service->saveFreeValues($game, [1 => 7]);

        $this->assertSame([1 => 7], $service->freeValues($game));
    }

    public function testSaveFreeValuesIgnoresTheComputedRulesAndTheRulesOutsideTheRound(): void
    {
        $game = $this->game([], $this->rule(1, ComputedMeasure::ValidatedSquares, 100))
            ->setStartedAt(new DateTimeImmutable())
            ->setFinishedAt(new DateTimeImmutable());

        $service = $this->createService();
        $service->saveFreeValues($game, [1 => 4, 2 => 5]);

        $this->assertSame([], $service->freeValues($game));
    }

    public function testSaveFreeValuesIsIgnoredUntilTheGameIsStopped(): void
    {
        $game = $this->game([], $this->rule(1, null, 10))->setStartedAt(new DateTimeImmutable());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $service = $this->createService($em);
        $this->assertFalse($service->saveFreeValues($game, [1 => 4]));
        $this->assertSame([1 => 0], $service->freeValues($game));
    }
}

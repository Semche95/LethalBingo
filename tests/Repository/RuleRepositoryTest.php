<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\ComputedMeasure;
use App\Entity\Rule;
use App\Repository\RuleRepository;
use App\Tests\Controller\ControllerTestCase;

class RuleRepositoryTest extends ControllerTestCase
{
    public function testListsTheComputedRulesBeforeTheFreeRules(): void
    {
        $first = $this->createFreeRule('First');
        $second = $this->createFreeRule('Second');

        $rules = self::getContainer()->get(RuleRepository::class)->findAllOrdered();

        $computedCount = \count(ComputedMeasure::cases());
        self::assertSame(
            ComputedMeasure::cases(),
            array_map(static fn (Rule $rule): ?ComputedMeasure => $rule->getComputedMeasure(), \array_slice($rules, 0, $computedCount)),
        );
        self::assertNotContains(true, array_map(static fn (Rule $rule): bool => $rule->isComputed(), \array_slice($rules, $computedCount)));
        self::assertSame([$first, $second], \array_slice($rules, -2));
    }
}

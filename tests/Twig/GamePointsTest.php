<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\ComputedMeasure;
use App\Entity\Game;
use App\Entity\Rule;
use App\Tests\Controller\ControllerTestCase;
use DateTimeImmutable;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class GamePointsTest extends ControllerTestCase
{
    use InteractsWithLiveComponents;

    public function testTheRefereeEntersTheFreeValuesOnceTheGameIsStoppedAndSeesThePoints(): void
    {
        $game = $this->createPlayedGame()->setFinishedAt(new DateTimeImmutable());
        $validatedSquares = $this->em->getRepository(Rule::class)->findOneBy(['computedMeasure' => ComputedMeasure::ValidatedSquares])
            ?? new Rule()->setComputedMeasure(ComputedMeasure::ValidatedSquares)->setCoefficient(100);
        $this->em->persist($validatedSquares);
        $freeRule = $this->createFreeRule('Days', -50);
        $game->getRound()->addRule($validatedSquares)->addRule($freeRule);
        $this->em->flush();

        $component = $this->createLiveComponent('GamePoints', ['game' => $game], $this->client)
            ->actingAs($this->createUser(['ROLE_REFEREE']))
            ->set('ruleValues.' . $freeRule->getId(), '3')
            ->call('saveRuleValues');

        self::assertSame(3, $this->reload(Game::class, $game->getId())->getRuleValues()->first()->getValue());
        $total = $component->render()->crawler()->filter('tfoot th')->last()->text();
        self::assertSame(str_replace('-', '−', (string) (2 * $validatedSquares->getCoefficient() - 150)), $total);
    }

    public function testThePointsFollowTheTypedValueBeforeItIsSaved(): void
    {
        $game = $this->createPlayedGame()->setFinishedAt(new DateTimeImmutable());
        $freeRule = $this->createFreeRule('Days', -50);
        $game->getRound()->addRule($freeRule);
        $this->em->flush();

        $total = $this->createLiveComponent('GamePoints', ['game' => $game], $this->client)
            ->actingAs($this->createUser(['ROLE_REFEREE']))
            ->set('ruleValues.' . $freeRule->getId(), '3')
            ->render()
            ->crawler()
            ->filter('tfoot th')
            ->last()
            ->text();

        self::assertSame('−150', $total);
        self::assertCount(0, $this->reload(Game::class, $game->getId())->getRuleValues());
    }

    public function testNoValueIsEnteredBeforeTheGameIsStopped(): void
    {
        $game = $this->createPlayedGame();
        $game->getRound()->addRule($this->createFreeRule());
        $this->em->flush();

        $crawler = $this->createLiveComponent('GamePoints', ['game' => $game], $this->client)
            ->actingAs($this->createUser(['ROLE_REFEREE']))
            ->render()
            ->crawler();

        self::assertCount(0, $crawler->filter('input'));
        self::assertCount(0, $crawler->filter('button'));
    }
}

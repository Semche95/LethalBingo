<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\Team;
use App\Service\GameService;
use App\Tests\Controller\ControllerTestCase;
use Closure;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

class TeamFormTest extends ControllerTestCase
{
    use InteractsWithLiveComponents;

    /**
     * @return iterable<string, array{Closure(TestLiveComponent): mixed}>
     */
    public static function actionProvider(): iterable
    {
        yield 'save' => [static fn (TestLiveComponent $component): mixed => $component->submitForm([
            'team_form' => ['name' => 'Renamed team', 'members' => [['name' => 'Renamed captain', 'captain' => '1']]],
        ], 'save')];
        yield 'add a member' => [static fn (TestLiveComponent $component): mixed => $component->call('addCollectionItem', ['name' => 'members'])];
        yield 'remove a member' => [static fn (TestLiveComponent $component): mixed => $component->call('removeCollectionItem', ['name' => 'members', 'index' => 0])];
    }

    /**
     * @param Closure(TestLiveComponent): mixed $action
     */
    #[DataProvider('actionProvider')]
    public function testATeamWithAStartedGameIsLocked(Closure $action): void
    {
        $team = $this->createLockedTeam();
        $name = $team->getName();

        try {
            $action($this->createTeamFormComponent($team));
            self::fail('A locked team must not be changed.');
        } catch (BadRequestHttpException) {
        }

        $reloaded = $this->reload(Team::class, $team->getId());
        self::assertSame($name, $reloaded->getName());
        self::assertSame(['Captain'], $reloaded->getMembers()->map(static fn ($member): string => $member->getName())->getValues());
    }

    public function testALockedTeamIsShownDisabledWithItsReason(): void
    {
        $html = (string) $this->createTeamFormComponent($this->createLockedTeam())->render();

        self::assertStringContainsString('Cette équipe a une partie en cours ou terminée', $html);
        self::assertStringContainsString('<fieldset disabled>', $html);
        self::assertStringNotContainsString('Enregistrer', $html);
    }

    public function testSavesATeamWhoseGameIsNotStarted(): void
    {
        $team = $this->createUnstartedTeam();

        $this->createTeamFormComponent($team)->submitForm([
            'team_form' => ['name' => 'Renamed team', 'members' => [['name' => 'Renamed captain', 'captain' => '1']]],
        ], 'save');

        self::assertSame('Renamed team', $this->reload(Team::class, $team->getId())->getName());
    }

    public function testAddsAndRemovesAMemberOfATeamWhoseGameIsNotStarted(): void
    {
        $component = $this->createTeamFormComponent($this->createUnstartedTeam());

        $html = (string) $component->call('addCollectionItem', ['name' => 'members'])->render();
        self::assertStringContainsString('team_form[members][1][name]', $html);
        self::assertStringNotContainsString('<fieldset disabled>', $html);

        $html = (string) $component->call('removeCollectionItem', ['name' => 'members', 'index' => 1])->render();
        self::assertStringNotContainsString('team_form[members][1][name]', $html);
    }

    public function testTheResetUnlocksTheTeam(): void
    {
        $game = $this->createGame()->setStartedAt(new DateTimeImmutable('-5 minutes'));
        $this->em->flush();
        self::getContainer()->get(GameService::class)->reset($game);
        $team = $this->reload(Team::class, $game->getTeam()->getId());
        self::assertFalse($team->hasStartedGame());

        $this->createTeamFormComponent($team)->submitForm([
            'team_form' => ['name' => 'Renamed team', 'members' => [['name' => 'Captain', 'captain' => '1']]],
        ], 'save');

        self::assertSame('Renamed team', $this->reload(Team::class, $team->getId())->getName());
    }

    private function createLockedTeam(): Team
    {
        $game = $this->createGame()->setStartedAt(new DateTimeImmutable('-5 minutes'));
        $this->em->flush();

        // reloaded so that its games are loaded from the database
        return $this->reload(Team::class, $game->getTeam()->getId());
    }

    private function createUnstartedTeam(): Team
    {
        return $this->reload(Team::class, $this->createGame()->getTeam()->getId());
    }

    private function createTeamFormComponent(Team $team): TestLiveComponent
    {
        return $this->createLiveComponent('TeamForm', ['team' => $team, 'mode' => 'edit'], $this->client)
            ->actingAs($this->createUser(['ROLE_REFEREE']));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Controller\Referee;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Tests\Controller\ControllerTestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

class TeamControllerTest extends ControllerTestCase
{
    use InteractsWithLiveComponents;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsReferee();
    }

    public function testAddPageRendersTheFullLayout(): void
    {
        $this->client->request('GET', '/referee/team/add');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1.dashboard-title', 'Nouvelle équipe');
    }

    public function testAddFragmentRendersOnlyTheForm(): void
    {
        $this->client->request('GET', '/referee/team/add', server: ['HTTP_X_FRAGMENT_REQUEST' => '1']);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
        self::assertSelectorNotExists('h1.dashboard-title');
    }

    public function testCreatesATeamWithItsCaptain(): void
    {
        $this->createTeamFormComponent()->submitForm([
            'team_form' => [
                'name' => 'Brand new team',
                'members' => [['name' => 'Alice', 'captain' => '1']],
            ],
        ], 'save');

        $team = $this->em->getRepository(Team::class)->findOneBy(['name' => 'Brand new team']);
        self::assertNotNull($team);
        self::assertResponseRedirects('/referee/team/edit/' . $team->getId());
        self::assertSame(['Alice'], $team->getMembers()->map(static fn (TeamMember $member): string => $member->getName())->toArray());
        self::assertTrue($team->getMembers()->first()->isCaptain());
    }

    public function testRejectsAnEmptyTeam(): void
    {
        $teamCount = $this->em->getRepository(Team::class)->count();

        $component = $this->createTeamFormComponent();

        try {
            $component->submitForm([
                'team_form' => [
                    'name' => '',
                    'members' => [['name' => '', 'captain' => '1']],
                ],
            ], 'save');
            self::fail('A team without name must be rejected.');
        } catch (UnprocessableEntityHttpException) {
        }

        self::assertSame($teamCount, $this->em->getRepository(Team::class)->count());
    }

    public function testEditPageShowsTheTeam(): void
    {
        $team = $this->createTeam();

        $this->client->request('GET', '/referee/team/edit/' . $team->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1.dashboard-title', 'Modifier l\'équipe');
        self::assertSelectorExists(sprintf('input[value="%s"]', $team->getName()));
    }

    public function testEditFragmentRendersOnlyTheForm(): void
    {
        $team = $this->createTeam();

        $this->client->request('GET', '/referee/team/edit/' . $team->getId(), server: ['HTTP_X_FRAGMENT_REQUEST' => '1']);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('input[value="%s"]', $team->getName()));
        self::assertSelectorNotExists('h1.dashboard-title');
    }

    public function testDeletesATeam(): void
    {
        $team = $this->createTeam();
        $teamId = $team->getId();
        $token = $this->fetchToken('/referee/team/edit/' . $teamId, '#delete-team-modal');

        $this->client->request('POST', '/referee/team/delete/' . $teamId, ['token' => $token]);

        self::assertResponseRedirects('/referee/team');
        self::assertNull($this->reload(Team::class, $teamId));
        $this->client->followRedirect();
        self::assertFlash('success', 'Équipe supprimée');
    }

    /**
     * The team form as rendered by the add page: a new team with an empty captain.
     */
    private function createTeamFormComponent(): TestLiveComponent
    {
        $team = new Team()->addMember(new TeamMember()->setCaptain(true));

        return $this->createLiveComponent('TeamForm', ['team' => $team, 'mode' => 'add'], $this->client);
    }

    public function testDeleteWithAnInvalidTokenIsRejected(): void
    {
        $team = $this->createTeam();

        $this->client->request('POST', '/referee/team/delete/' . $team->getId(), ['token' => 'invalid']);

        self::assertResponseStatusCodeSame(400);
        self::assertNotNull($this->reload(Team::class, $team->getId()));
    }
}

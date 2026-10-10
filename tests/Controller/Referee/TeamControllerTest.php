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

    public function testAddFragmentRendersOnlyTheForm(): void
    {
        $this->client->request('GET', '/referee/team/add', server: ['HTTP_X_FRAGMENT_REQUEST' => '1']);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
        self::assertSelectorNotExists('h1.dashboard-title');
    }

    public function testAddPageIsNotFoundOutsideTheModal(): void
    {
        $this->client->request('GET', '/referee/team/add');

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditPageIsNotFoundOutsideTheModal(): void
    {
        $team = $this->createTeam();

        $this->client->request('GET', '/referee/team/edit/' . $team->getId());

        self::assertResponseStatusCodeSame(404);
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

    public function testListOpensTheRequestedTeamModal(): void
    {
        $team = $this->createTeam();

        $this->client->request('GET', '/referee/team/' . $team->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('a[href="/referee/team/edit/%d"][data-modal-loader-auto-open-value="true"][data-modal-loader-close-url-value="/referee/team"]', $team->getId()));
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
        $token = $this->fetchDeleteToken('/referee/team/edit/' . $teamId);

        $this->client->request('POST', '/referee/team/delete/' . $teamId, ['token' => $token]);

        self::assertResponseRedirects('/referee/team');
        self::assertNull($this->reload(Team::class, $teamId));
        $this->client->followRedirect();
        self::assertFlash('success', 'Équipe supprimée');
    }

    public function testRefusesToDeleteATeamWithGames(): void
    {
        $teamId = $this->createGame()->getTeam()->getId();
        $token = $this->fetchDeleteToken('/referee/team/edit/' . $teamId);

        $this->client->request('POST', '/referee/team/delete/' . $teamId, ['token' => $token]);

        self::assertResponseRedirects('/referee/team');
        self::assertNotNull($this->reload(Team::class, $teamId));
        $this->client->followRedirect();
        self::assertFlash('error', 'Impossible de supprimer une équipe qui a des parties');
    }

    private function fetchDeleteToken(string $url): string
    {
        $crawler = $this->client->request('GET', $url, server: ['HTTP_X_FRAGMENT_REQUEST' => '1']);

        return (string) $crawler->filter('#delete-team-modal input[name="token"]')->attr('value');
    }

    /**
     * The team form as rendered by the add modal: a new team with an empty captain.
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

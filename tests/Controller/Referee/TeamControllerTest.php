<?php

declare(strict_types=1);

namespace App\Tests\Controller\Referee;

use App\Entity\Team;
use App\Tests\Controller\ControllerTestCase;

class TeamControllerTest extends ControllerTestCase
{
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

    public function testDeleteWithAnInvalidTokenIsRejected(): void
    {
        $team = $this->createTeam();

        $this->client->request('POST', '/referee/team/delete/' . $team->getId(), ['token' => 'invalid']);

        self::assertResponseStatusCodeSame(400);
        self::assertNotNull($this->reload(Team::class, $team->getId()));
    }
}

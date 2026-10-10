<?php

declare(strict_types=1);

namespace App\Tests\Controller\Referee;

use App\Tests\Controller\ControllerTestCase;

class DashboardControllerTest extends ControllerTestCase
{
    public function testListsTheRecentGamesAndBingos(): void
    {
        $this->loginAsReferee();
        $game = $this->createGame($this->createRound(7, 3, complete: true));

        $this->client->request('GET', '/referee');

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('.activity-item-name', $game->getTeam() . ' · Round 3 Saison 7');
        self::assertAnySelectorTextContains('.activity-item-name', 'Round 3 Saison 7');
    }
}

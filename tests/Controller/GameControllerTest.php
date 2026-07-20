<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    public function testServerTimeRouteReturnsValidTimestamp(): void
    {
        $before = time();

        $client = static::createClient();
        $client->request('GET', '/game/server-time');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('serverTime', $data);
        $this->assertIsInt($data['serverTime']);

        $after = time();
        $this->assertGreaterThanOrEqual($before, $data['serverTime']);
        $this->assertLessThanOrEqual($after, $data['serverTime']);
    }
}

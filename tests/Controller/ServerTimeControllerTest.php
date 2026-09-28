<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\EventListener\ServerTimeHeaderListener;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServerTimeControllerTest extends WebTestCase
{
    public function testReturnsAnUncachedEmptyResponseStampedWithTheServerTime(): void
    {
        $client = self::createClient();

        $before = microtime(true);
        $client->request('GET', '/server-time');
        $after = microtime(true);

        $response = $client->getResponse();
        self::assertSame(204, $response->getStatusCode());
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $serverTiming = implode(', ', $response->headers->all('server-timing'));
        self::assertSame(1, preg_match('/' . ServerTimeHeaderListener::METRIC . ';desc="([0-9.]+)"/', $serverTiming, $matches));
        self::assertGreaterThanOrEqual($before, (float) $matches[1]);
        self::assertLessThanOrEqual($after, (float) $matches[1]);
    }
}

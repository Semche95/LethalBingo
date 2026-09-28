<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\ServerTimeHeaderListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ServerTimeHeaderListenerTest extends TestCase
{
    public function testStampsMainResponseWithCurrentServerTime(): void
    {
        $response = new Response();

        $before = microtime(true);
        new ServerTimeHeaderListener()($this->createEvent($response, HttpKernelInterface::MAIN_REQUEST));
        $after = microtime(true);

        $this->assertMatchesRegularExpression('/^app-time;desc="(\d+\.\d{6})"$/', $response->headers->get('Server-Timing'));
        preg_match('/desc="([^"]+)"/', $response->headers->get('Server-Timing'), $matches);
        $this->assertGreaterThanOrEqual(floor($before * 1e6) / 1e6, (float)$matches[1]);
        $this->assertLessThanOrEqual($after, (float)$matches[1]);
    }

    public function testKeepsExistingServerTimingMetrics(): void
    {
        $response = new Response(headers: ['Server-Timing' => 'db;dur=12']);

        new ServerTimeHeaderListener()($this->createEvent($response, HttpKernelInterface::MAIN_REQUEST));

        $this->assertCount(2, $response->headers->all('server-timing'));
    }

    public function testIgnoresSubRequests(): void
    {
        $response = new Response();

        new ServerTimeHeaderListener()($this->createEvent($response, HttpKernelInterface::SUB_REQUEST));

        $this->assertFalse($response->headers->has('Server-Timing'));
    }

    private function createEvent(Response $response, int $requestType): ResponseEvent
    {
        return new ResponseEvent($this->createStub(HttpKernelInterface::class), new Request(), $requestType, $response);
    }
}

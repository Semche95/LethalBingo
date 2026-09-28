<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\ProfilerCspNonceListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ProfilerCspNonceListenerTest extends TestCase
{
    public function testGivesEveryRequestTheSameNonce(): void
    {
        $first = $this->handle(new Request(), 'secret');
        $second = $this->handle(new Request(), 'secret');

        foreach (ProfilerCspNonceListener::HEADERS as $header) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $first->headers->get($header));
            $this->assertSame($first->headers->get($header), $second->headers->get($header));
        }
    }

    public function testDerivesTheNonceFromTheSecret(): void
    {
        $header = ProfilerCspNonceListener::HEADERS[0];

        $this->assertNotSame(
            $this->handle(new Request(), 'secret')->headers->get($header),
            $this->handle(new Request(), 'other secret')->headers->get($header),
        );
    }

    public function testKeepsNoncesSentByTheClient(): void
    {
        $request = new Request();
        $request->headers->set(ProfilerCspNonceListener::HEADERS[0], 'abc');

        $this->handle($request, 'secret');

        $this->assertSame('abc', $request->headers->get(ProfilerCspNonceListener::HEADERS[0]));
    }

    private function handle(Request $request, string $secret): Request
    {
        new ProfilerCspNonceListener($secret)(new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));

        return $request;
    }
}

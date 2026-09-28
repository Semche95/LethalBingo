<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\ContentSecurityPolicyListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ContentSecurityPolicyListenerTest extends TestCase
{
    public function testSetsThePolicyOnMainResponses(): void
    {
        $response = new Response();

        new ContentSecurityPolicyListener()($this->createEvent($response, HttpKernelInterface::MAIN_REQUEST));

        $this->assertSame(ContentSecurityPolicyListener::POLICY, $response->headers->get('Content-Security-Policy'));
    }

    public function testKeepsAPolicyAlreadySetByTheResponse(): void
    {
        $response = new Response(headers: ['Content-Security-Policy' => "default-src 'none'"]);

        new ContentSecurityPolicyListener()($this->createEvent($response, HttpKernelInterface::MAIN_REQUEST));

        $this->assertSame("default-src 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function testIgnoresSubRequests(): void
    {
        $response = new Response();

        new ContentSecurityPolicyListener()($this->createEvent($response, HttpKernelInterface::SUB_REQUEST));

        $this->assertFalse($response->headers->has('Content-Security-Policy'));
    }

    public function testForbidsInlineScriptsAndFraming(): void
    {
        $this->assertStringContainsString("script-src 'self';", ContentSecurityPolicyListener::POLICY);
        $this->assertStringContainsString("frame-ancestors 'none'", ContentSecurityPolicyListener::POLICY);
    }

    private function createEvent(Response $response, int $requestType): ResponseEvent
    {
        return new ResponseEvent($this->createStub(HttpKernelInterface::class), new Request(), $requestType, $response);
    }
}

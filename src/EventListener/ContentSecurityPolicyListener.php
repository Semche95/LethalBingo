<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Restricts what pages may load or run to the app's own origin. The other
 * security headers are set by Caddy for every response; this one is set here so
 * the profiler toolbar, which runs later, can add its nonces in dev.
 */
#[AsEventListener(event: KernelEvents::RESPONSE)]
final class ContentSecurityPolicyListener
{
    public const string POLICY = "default-src 'self'; "
        . "script-src 'self'; "
        // Inline style attributes (grid positions, team colors)
        . "style-src 'self' 'unsafe-inline'; "
        // Bootstrap and Tabler draw form controls with SVG data URIs
        . "img-src 'self' data:; "
        . "font-src 'self'; "
        . "connect-src 'self'; "
        . "media-src 'self'; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'none'";

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;
        if (!$headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', self::POLICY);
        }
    }
}

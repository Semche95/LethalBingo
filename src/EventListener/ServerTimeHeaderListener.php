<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Stamps every response with the server time at which it leaves the kernel. The
 * browser records when each response arrived, which gives the game timer a
 * server clock estimate that can only lag, never lead (docs/TIMER.md section 1).
 */
#[AsEventListener(event: KernelEvents::RESPONSE, priority: -2048)]
final class ServerTimeHeaderListener
{
    public const METRIC = 'app-time';

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Lowest priority so the timestamp is taken after every other listener
        // (including the profiler toolbar injection), right before sending.
        $event->getResponse()->headers->set(
            'Server-Timing',
            sprintf('%s;desc="%.6F"', self::METRIC, microtime(true)),
            false,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Dev only: gives the profiler toolbar the same CSP nonce on every response.
 *
 * The profiler adds a random nonce to script-src on each response so its
 * toolbar script may run. Turbo Drive swaps pages without reloading the
 * document, so the browser keeps the policy (and nonce) of the first page and
 * blocks the toolbar script of every later page. The profiler reuses the nonces
 * it finds in these request headers, so a nonce derived from the app secret
 * stays valid across Turbo visits, logins and logouts (like Rails' session
 * nonce, but without depending on a session that logout destroys).
 *
 * Trade-off: a constant nonce is less strict than a per-response one, which is
 * acceptable because this listener only exists in dev; prod has no toolbar and
 * keeps a script-src without nonce or 'unsafe-inline'.
 */
#[When(env: 'dev')]
#[AsEventListener(event: KernelEvents::REQUEST, priority: 512)]
final readonly class ProfilerCspNonceListener
{
    public const array HEADERS = ['X-SymfonyProfiler-Script-Nonce', 'X-SymfonyProfiler-Style-Nonce'];

    public function __construct(
        #[Autowire(param: 'kernel.secret')]
        private string $secret,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $headers = $event->getRequest()->headers;
        $nonce = substr(hash_hmac('sha256', 'profiler-csp-nonce', $this->secret), 0, 32);

        foreach (self::HEADERS as $header) {
            if (!$headers->has($header)) {
                $headers->set($header, $nonce);
            }
        }
    }
}

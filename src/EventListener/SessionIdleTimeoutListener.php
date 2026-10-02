<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Clock\ClockInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Logs the user out after a long period without real activity (docs/SECURITY.md,
 * "Session Inactivity"). The csrf-refresh ping keeps the PHP session alive on
 * pages left open, so without this rule an unattended tab would stay logged in
 * forever.
 *
 * Activity is what the user does: page loads, Turbo visits, form submits. Requests
 * the page sends by itself (the ping, Live Component re-renders pushed by Mercure,
 * Turbo prefetches, the timer resync) never count. Clicks and key presses that
 * only trigger Live Component actions are reported by the ping as the number of
 * seconds since the last interaction in the page ("idle"). The ping runs every
 * few minutes and the timeout is hours long, so that lag does not matter.
 *
 * The timeout is longer (days) when "Se souvenir de moi" was ticked at login.
 */
#[AsEventListener(event: KernelEvents::REQUEST, method: 'onRequest', priority: 4)]
// After CheckRememberMeConditionsListener (-32), which enables the remember-me badge.
#[AsEventListener(event: LoginSuccessEvent::class, method: 'onLoginSuccess', priority: -48)]
final readonly class SessionIdleTimeoutListener
{
    use TargetPathTrait;

    /** Unix time of the last activity of the logged-in user */
    public const string LAST_ACTIVITY = '_last_activity';
    /** Set when "Se souvenir de moi" was ticked at login: longer timeout */
    public const string REMEMBERED = '_remembered';
    /** Left in the new anonymous session so a later ping still gets a 401 */
    public const string LOGGED_OUT_FOR_INACTIVITY = '_logged_out_for_inactivity';
    public const string FLASH_MESSAGE = 'Vous avez été déconnecté après une longue période d\'inactivité.';

    private const string PING_ROUTE = 'app_csrf_tokens';
    /** Requests sent by the page itself, without any user action */
    private const array PASSIVE_ROUTES = [self::PING_ROUTE, 'app_server_time'];
    private const string FIREWALL = 'main';

    public function __construct(
        private Security $security,
        private ClockInterface $clock,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire(param: 'app.session_idle_timeout')]
        private int $idleTimeout,
        #[Autowire(param: 'app.remembered_session_idle_timeout')]
        private int $rememberedIdleTimeout,
    ) {
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        // No session cookie: nobody to log out, and no session to start for nothing.
        if (!$event->isMainRequest() || !$request->hasPreviousSession()) {
            return;
        }

        $session = $request->getSession();
        if ($this->security->getUser() === null) {
            // Logged out meanwhile by another request of the page (Live re-render, timer
            // resync...): the ping must still tell the page to show the login page.
            if ($this->isPing($request) && $session->has(self::LOGGED_OUT_FOR_INACTIVITY)) {
                $event->setResponse($this->sessionExpiredResponse());
            }

            return;
        }

        $now = $this->clock->now()->getTimestamp();
        // Sessions opened before this rule existed start counting now.
        $lastActivity = $session->get(self::LAST_ACTIVITY) ?? $now;
        $idle = $this->isPing($request) ? $this->reportedIdle($request) : null;
        if ($idle !== null) {
            $lastActivity = max($lastActivity, $now - $idle);
        }

        $idleTimeout = $session->get(self::REMEMBERED) ? $this->rememberedIdleTimeout : $this->idleTimeout;
        if ($now - $lastActivity > $idleTimeout) {
            $event->setResponse($this->logOut($request));

            return;
        }

        $lastActivity = $this->isActivity($request) ? $now : $lastActivity;
        if ($session->get(self::LAST_ACTIVITY) !== $lastActivity) {
            $session->set(self::LAST_ACTIVITY, $lastActivity);
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        $session->set(self::LAST_ACTIVITY, $this->clock->now()->getTimestamp());
        $session->remove(self::LOGGED_OUT_FOR_INACTIVITY);

        // Box ticked on the login form, or logged back in by the remember-me cookie itself.
        $remembered = $event->getPassport()->getBadge(RememberMeBadge::class)?->isEnabled()
            || $event->getAuthenticatedToken() instanceof RememberMeToken;
        if ($remembered) {
            $session->set(self::REMEMBERED, true);
        } else {
            $session->remove(self::REMEMBERED);
        }
    }

    private function logOut(Request $request): Response
    {
        // Not the logout route: there is no CSRF token to check.
        $this->security->logout(false);

        // The logout invalidated the session: these go into the new, anonymous one.
        $session = $request->getSession();
        $session->set(self::LOGGED_OUT_FOR_INACTIVITY, true);
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('warning', self::FLASH_MESSAGE);
        }

        if ($this->isPing($request)) {
            return $this->sessionExpiredResponse();
        }

        if ($request->isMethod('GET') && !$this->isLiveComponent($request) && !$request->isXmlHttpRequest()) {
            $this->saveTargetPath($session, self::FIREWALL, $request->getUri());
        }

        // A Live Component request gets this redirect as an empty response with an
        // X-Live-Redirect header (LiveComponentSubscriber), that the Live frontend
        // follows with a Turbo visit.
        return new RedirectResponse($this->urlGenerator->generate('app_login'), Response::HTTP_SEE_OTHER);
    }

    private function sessionExpiredResponse(): JsonResponse
    {
        $response = new JsonResponse(['error' => 'session_expired'], Response::HTTP_UNAUTHORIZED);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    private function isActivity(Request $request): bool
    {
        return !\in_array($request->attributes->get('_route'), self::PASSIVE_ROUTES, true)
            && !$this->isLiveComponent($request)
            && !$this->isPrefetch($request);
    }

    private function isPing(Request $request): bool
    {
        return $request->attributes->get('_route') === self::PING_ROUTE;
    }

    /**
     * Seconds since the last click or key press in the page, as reported by the
     * ping. A client lying about it only keeps its own session open.
     */
    private function reportedIdle(Request $request): ?int
    {
        $idle = filter_var($request->query->get('idle'), \FILTER_VALIDATE_INT);

        return $idle === false ? null : max(0, $idle);
    }

    private function isLiveComponent(Request $request): bool
    {
        return $request->attributes->has('_live_component')
            || \in_array('application/vnd.live-component+html', $request->getAcceptableContentTypes(), true);
    }

    private function isPrefetch(Request $request): bool
    {
        foreach (['Sec-Purpose', 'X-Sec-Purpose', 'Purpose'] as $header) {
            if (str_contains(strtolower((string) $request->headers->get($header)), 'prefetch')) {
                return true;
            }
        }

        return false;
    }
}

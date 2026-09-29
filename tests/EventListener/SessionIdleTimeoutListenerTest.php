<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\SessionIdleTimeoutListener;
use App\Tests\Controller\ControllerTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

class SessionIdleTimeoutListenerTest extends ControllerTestCase
{
    use ClockSensitiveTrait;

    private const int TIMEOUT = 8 * 3600;
    private const int REMEMBERED_TIMEOUT = 30 * 24 * 3600;
    private const string REMEMBER_ME_COOKIE = 'REMEMBERME';

    private MockClock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $clock = self::mockTime('2026-01-01 08:00:00');
        \assert($clock instanceof MockClock);
        $this->clock = $clock;
    }

    public function testDefaultTimeoutIsEightHours(): void
    {
        self::assertSame(self::TIMEOUT, self::getContainer()->getParameter('app.session_idle_timeout'));
    }

    public function testDefaultRememberedTimeoutIsThirtyDays(): void
    {
        self::assertSame(self::REMEMBERED_TIMEOUT, self::getContainer()->getParameter('app.remembered_session_idle_timeout'));
    }

    public function testLoginStartsTheSessionAsActive(): void
    {
        $user = $this->createUser(['ROLE_REFEREE']);

        $this->client->request('GET', '/user/login');
        $this->client->submitForm('Se connecter', ['_username' => $user->getUsername(), '_password' => self::PASSWORD]);

        self::assertSame($this->clock->now()->getTimestamp(), $this->lastActivity());
    }

    public function testRequestAfterTheTimeoutLogsOutAndRedirectsToTheLoginPage(): void
    {
        $this->startActiveSession();

        $this->clock->sleep(self::TIMEOUT + 1);
        $this->client->request('GET', '/referee/team');

        self::assertResponseRedirects('/user/login', 303);
        $this->client->followRedirect();
        self::assertFlash('warning', SessionIdleTimeoutListener::FLASH_MESSAGE);
        $this->assertLoggedOut();
    }

    public function testLoggingInAgainAfterTheTimeoutGoesBackToTheRequestedPage(): void
    {
        $user = $this->startActiveSession();

        $this->clock->sleep(self::TIMEOUT + 1);
        $this->client->request('GET', '/referee/team');
        $this->client->followRedirect();
        $this->client->submitForm('Se connecter', ['_username' => $user->getUsername(), '_password' => self::PASSWORD]);

        self::assertResponseRedirects('http://localhost/referee/team');
    }

    public function testRememberedSessionOutlivesTheDefaultTimeout(): void
    {
        $this->logInRemembered();

        $this->clock->sleep(self::TIMEOUT + 1);
        $this->client->request('GET', '/referee/team');

        self::assertResponseIsSuccessful();
    }

    public function testRequestAfterTheRememberedTimeoutLogsOutAndClearsTheRememberMeCookie(): void
    {
        $this->logInRemembered();

        $this->clock->sleep(self::REMEMBERED_TIMEOUT + 1);
        $this->client->request('GET', '/referee/team');

        self::assertResponseRedirects('/user/login', 303);
        self::assertNull($this->client->getCookieJar()->get(self::REMEMBER_ME_COOKIE));
        $this->client->followRedirect();
        self::assertFlash('warning', SessionIdleTimeoutListener::FLASH_MESSAGE);
        $this->assertLoggedOut();
    }

    public function testLoginByTheRememberMeCookieKeepsTheRememberedTimeout(): void
    {
        $this->logInRemembered();
        $sessionName = $this->client->getRequest()->getSession()->getName();

        // Session lost (browser closed): only the remember-me cookie logs the user back in.
        $this->client->getCookieJar()->expire($sessionName);
        $this->client->request('GET', '/referee/team');
        self::assertResponseIsSuccessful();

        $this->clock->sleep(self::TIMEOUT + 1);
        $this->client->request('GET', '/referee/team');

        self::assertResponseIsSuccessful();
    }

    public function testRequestBeforeTheTimeoutKeepsTheSessionAndRefreshesTheActivity(): void
    {
        $this->startActiveSession();

        $this->clock->sleep(self::TIMEOUT - 60);
        $this->client->request('GET', '/referee');
        self::assertResponseIsSuccessful();
        self::assertSame($this->clock->now()->getTimestamp(), $this->lastActivity());

        // Past the timeout counted from the first request, not from the last one.
        $this->clock->sleep(120);
        $this->client->request('GET', '/referee');
        self::assertResponseIsSuccessful();
    }

    public function testPingAloneDoesNotRefreshTheActivity(): void
    {
        $this->startActiveSession();
        $start = $this->lastActivity();

        $this->clock->sleep(self::TIMEOUT - 60);
        $this->client->request('GET', '/csrf-tokens');
        self::assertResponseIsSuccessful();
        self::assertSame($start, $this->lastActivity());

        $this->clock->sleep(120);
        $this->client->request('GET', '/referee');
        self::assertResponseRedirects('/user/login');
    }

    public function testPingReportingALongIdleTimeDoesNotRefreshTheActivity(): void
    {
        $this->startActiveSession();
        $start = $this->lastActivity();

        $this->clock->sleep(4 * 3600);
        $this->ping(idle: 4 * 3600);

        self::assertResponseIsSuccessful();
        self::assertSame($start, $this->lastActivity());
    }

    public function testPingReportingARecentInteractionRefreshesTheActivity(): void
    {
        $this->startActiveSession();

        $this->clock->sleep(self::TIMEOUT - 60);
        $this->ping(idle: 30);
        self::assertResponseIsSuccessful();
        self::assertSame($this->clock->now()->getTimestamp() - 30, $this->lastActivity());

        $this->clock->sleep(self::TIMEOUT - 60);
        $this->client->request('GET', '/referee');
        self::assertResponseIsSuccessful();
    }

    public function testPingCountsAnInteractionReportedAfterTheTimeoutWhenItHappenedBefore(): void
    {
        $this->startActiveSession();

        // Clicked 10 minutes ago, 7h55 after the last page load: not idle for 8 hours.
        $this->clock->sleep(self::TIMEOUT + 300);
        $this->ping(idle: 600);

        self::assertResponseIsSuccessful();
    }

    public function testPingIgnoresANegativeIdleTime(): void
    {
        $this->startActiveSession();

        $this->clock->sleep(3600);
        $this->ping(idle: -3600);

        self::assertSame($this->clock->now()->getTimestamp(), $this->lastActivity());
    }

    public function testPingAfterTheTimeoutAnswers401AndLogsOut(): void
    {
        $this->startActiveSession();

        $this->clock->sleep(self::TIMEOUT + 1);
        $this->ping(idle: self::TIMEOUT + 1);

        self::assertResponseStatusCodeSame(401);
        self::assertSame(['error' => 'session_expired'], json_decode((string) $this->client->getResponse()->getContent(), true));
        $this->assertLoggedOut();
    }

    public function testPingAfterALogoutForInactivityByAnotherRequestAnswers401(): void
    {
        $this->startActiveSession();

        $this->clock->sleep(self::TIMEOUT + 1);
        $this->client->request('GET', '/server-time');
        self::assertResponseRedirects('/user/login');
        $this->ping(idle: self::TIMEOUT + 1);

        self::assertResponseStatusCodeSame(401);
    }

    public function testPingOfASessionLostOtherwiseStillRedirectsToTheLoginPage(): void
    {
        $this->client->request('GET', '/csrf-tokens');

        self::assertResponseRedirects('/user/login');
    }

    public function testLiveComponentRequestDoesNotCountAsActivity(): void
    {
        $this->startActiveSession();
        $start = $this->lastActivity();

        $this->clock->sleep(self::TIMEOUT - 60);
        $this->requestLiveComponent();
        self::assertResponseIsSuccessful();
        self::assertSame($start, $this->lastActivity());

        $this->clock->sleep(120);
        $this->client->request('GET', '/referee');
        self::assertResponseRedirects('/user/login');
    }

    public function testLiveComponentRequestAfterTheTimeoutRedirectsTheWholePage(): void
    {
        $this->startActiveSession();

        $this->clock->sleep(self::TIMEOUT + 1);
        $this->requestLiveComponent();

        self::assertResponseStatusCodeSame(204);
        self::assertResponseHeaderSame('X-Live-Redirect', '1');
        self::assertResponseHeaderSame('Location', '/user/login');
        $this->assertLoggedOut();
    }

    public function testPrefetchRequestDoesNotCountAsActivity(): void
    {
        $this->startActiveSession();
        $start = $this->lastActivity();

        $this->clock->sleep(3600);
        $this->client->request('GET', '/referee/team', server: ['HTTP_X_SEC_PURPOSE' => 'prefetch']);
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/referee/team', server: ['HTTP_SEC_PURPOSE' => 'prefetch;prerender']);
        self::assertResponseIsSuccessful();

        self::assertSame($start, $this->lastActivity());
    }

    public function testTimerResyncDoesNotCountAsActivity(): void
    {
        $this->startActiveSession();
        $start = $this->lastActivity();

        $this->clock->sleep(3600);
        $this->client->request('GET', '/server-time');

        self::assertSame($start, $this->lastActivity());
    }

    private function startActiveSession(): User
    {
        $user = $this->loginAsReferee();
        $this->client->request('GET', '/referee/team');
        self::assertResponseIsSuccessful();
        self::assertSame($this->clock->now()->getTimestamp(), $this->lastActivity());

        return $user;
    }

    private function logInRemembered(): void
    {
        $user = $this->createUser(['ROLE_REFEREE']);

        $this->client->request('GET', '/user/login');
        $this->client->submitForm('Se connecter', [
            '_username' => $user->getUsername(),
            '_password' => self::PASSWORD,
            '_remember_me' => true,
        ]);
        self::assertNotNull($this->client->getCookieJar()->get(self::REMEMBER_ME_COOKIE));
    }

    private function ping(int $idle): void
    {
        $this->client->request('GET', '/csrf-tokens', ['idle' => (string) $idle]);
    }

    /**
     * Re-renders the TeamList component of the last page, as the Live frontend does.
     */
    private function requestLiveComponent(): void
    {
        $props = $this->client->getCrawler()->filter('[data-live-name-value="TeamList"]')->attr('data-live-props-value');
        $this->client->request('POST', '/_components/TeamList', [
            'data' => json_encode(['props' => json_decode((string) $props, true)], \JSON_THROW_ON_ERROR),
        ], server: [
            'HTTP_ACCEPT' => 'application/vnd.live-component+html',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
    }

    private function lastActivity(): ?int
    {
        return $this->client->getRequest()->getSession()->get(SessionIdleTimeoutListener::LAST_ACTIVITY);
    }

    private function assertLoggedOut(): void
    {
        $this->client->request('GET', '/referee');
        self::assertResponseRedirects('/user/login');
    }
}

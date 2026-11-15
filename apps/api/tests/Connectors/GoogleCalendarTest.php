<?php

namespace Tests\App\Connectors;

use App\Connectors\Google\GoogleCalendar;
use App\Connectors\Google\GoogleEndpoints;
use App\Connectors\Google\GoogleTokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\CalendarGone;
use Prospecter\Core\Connectors\Domain\Exception\CalendarRefusedThePush;
use Prospecter\Core\Connectors\Domain\Model\CalendarEvent;
use Prospecter\Core\Connectors\Domain\Model\Channel;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The calendar « Prospecter » of a member at Google, as the spike #92 saw it answer (ADR 0127): each call with an
 * access token refreshed through the client of the account, an event identified by its exchange, put first, created
 * with its id when Google does not know it.
 */
class GoogleCalendarTest extends TestCase
{
    private const string TOKEN_URL = 'https://oauth2.google.test/token';
    private const string CALENDAR_URL = 'https://www.googleapis.test/calendar/v3';
    private const string EXCHANGE = '0190a1c0-0000-7000-8000-0000000000ee';
    private const string CONTACT = '0190a1c0-0000-7000-8000-0000000000ad';
    private const string ORGANIZATION = '0190a1c0-0000-7000-8000-0000000000ac';

    /** @var list<array{string, string, array<string, mixed>|null}> the calls to the calendar, with their body */
    private array $calls = [];

    public function testTheCalendarOfProspecterIsCreatedInTheTimeOfParis(): void
    {
        $calendar = $this->calendar(new JsonMockResponse(['id' => 'the-calendar']))->create(self::client(), new RefreshToken('a-token'));

        $this->assertSame('the-calendar', $calendar);
        $this->assertSame([['POST', self::CALENDAR_URL.'/calendars', ['summary' => 'Prospecter', 'timeZone' => 'Europe/Paris']]], $this->calls);
    }

    public function testAnEventIsPutWithItsWordsItsMomentInParisAndItsReminder(): void
    {
        $this->calendar(new JsonMockResponse(['id' => self::eventId()]))->put(self::client(), new RefreshToken('a-token'), 'the-calendar', self::event(reminder: 10));

        $this->assertSame([['PUT', self::CALENDAR_URL.'/calendars/the-calendar/events/'.self::eventId().'?sendUpdates=none', [
            'id' => self::eventId(),
            'status' => 'confirmed',
            'summary' => 'Visio · Ada Lovelace (ACME)',
            'location' => 'https://meet.google.com/abc-defg-hij',
            'description' => 'https://front.test/contacts/'.self::CONTACT,
            'start' => ['dateTime' => '2026-11-17T14:00:00+01:00', 'timeZone' => 'Europe/Paris'],
            'end' => ['dateTime' => '2026-11-17T14:30:00+01:00', 'timeZone' => 'Europe/Paris'],
            'reminders' => ['useDefault' => false, 'overrides' => [['method' => 'popup', 'minutes' => 10]]],
        ]]], $this->calls);
    }

    public function testAnEventWithTheOrganizationItselfLeadsToItsCardWithoutReminder(): void
    {
        $event = new CalendarEvent(new ExchangeId(self::EXCHANGE), Channel::Meeting, new \DateTimeImmutable('2026-11-17 08:00 UTC'), new \DateTimeImmutable('2026-11-17 09:00 UTC'), null, null, null, new OrganizationId(self::ORGANIZATION), 'ACME', null);

        $this->calendar(new JsonMockResponse([]))->put(self::client(), new RefreshToken('a-token'), 'the-calendar', $event);

        $body = $this->calls[0][2] ?? [];
        $this->assertSame(['Rendez-vous · ACME', 'https://front.test/organizations/'.self::ORGANIZATION, ['useDefault' => false, 'overrides' => []]], [$body['summary'], $body['description'], $body['reminders']]);
        $this->assertArrayNotHasKey('location', $body);
    }

    public function testAnEventGoogleDoesNotKnowIsCreatedWithItsId(): void
    {
        $this->calendar(new MockResponse('', ['http_code' => 404]), new JsonMockResponse([]))->put(self::client(), new RefreshToken('a-token'), 'the-calendar', self::event());

        $this->assertSame([['PUT', self::eventUrl()], ['POST', self::CALENDAR_URL.'/calendars/the-calendar/events?sendUpdates=none']], self::methodsAndUrls($this->calls));
        $this->assertSame(self::eventId(), $this->calls[1][2]['id'] ?? null);
    }

    public function testAnEventCreatedMeanwhileIsPutAgain(): void
    {
        $this->calendar(new MockResponse('', ['http_code' => 404]), new MockResponse('', ['http_code' => 409]), new JsonMockResponse([]))->put(self::client(), new RefreshToken('a-token'), 'the-calendar', self::event());

        $this->assertSame(['PUT', 'POST', 'PUT'], array_column($this->calls, 0));
    }

    public function testACalendarGoogleNoLongerHasIsGone(): void
    {
        $this->expectException(CalendarGone::class);

        $this->calendar(new MockResponse('', ['http_code' => 404]), new MockResponse('', ['http_code' => 404]))->put(self::client(), new RefreshToken('a-token'), 'the-calendar', self::event());
    }

    #[DataProvider('removals')]
    public function testAnEventRemovedOrAlreadyGoneIsWithdrawn(int $status): void
    {
        $this->calendar(new MockResponse('', ['http_code' => $status]))->remove(self::client(), new RefreshToken('a-token'), 'the-calendar', new ExchangeId(self::EXCHANGE));

        $this->assertSame([['DELETE', self::eventUrl()]], self::methodsAndUrls($this->calls));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function removals(): iterable
    {
        yield 'removed' => [204];
        yield 'unknown' => [404];
        yield 'removed before' => [410];
    }

    public function testATokenGoogleRefusesRevokesTheAccess(): void
    {
        $this->expectException(AccessRevoked::class);

        $google = new MockHttpClient(new JsonMockResponse(['error' => 'invalid_grant'], ['http_code' => 400]));
        $this->calendarOn($google)->put(self::client(), new RefreshToken('a-token'), 'the-calendar', self::event());
    }

    public function testAnAccessTokenRefusedRevokesTheAccess(): void
    {
        $this->expectException(AccessRevoked::class);

        $this->calendar(new MockResponse('', ['http_code' => 401]))->put(self::client(), new RefreshToken('a-token'), 'the-calendar', self::event());
    }

    /**
     * @param array<string, mixed> $error
     */
    #[DataProvider('passingFailures')]
    public function testAFailureThatMayPassIsThrownToBeTriedAgain(int $status, array $error): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/^Google answered/');

        $this->calendar(new JsonMockResponse($error, ['http_code' => $status]))->put(self::client(), new RefreshToken('a-token'), 'the-calendar', self::event());
    }

    /**
     * @return iterable<string, array{int, array<string, mixed>}>
     */
    public static function passingFailures(): iterable
    {
        yield 'too many requests' => [429, []];
        yield 'the rate of the project' => [403, ['error' => ['errors' => [['reason' => 'rateLimitExceeded']]]]];
        yield 'the rate of the member' => [403, ['error' => ['errors' => [['reason' => 'userRateLimitExceeded']]]]];
        yield 'unavailable' => [503, []];
    }

    public function testAnyOtherRefusalIsForGood(): void
    {
        $this->expectException(CalendarRefusedThePush::class);

        $this->calendar(new JsonMockResponse(['error' => ['errors' => [['reason' => 'forbidden']]]], ['http_code' => 403]))->put(self::client(), new RefreshToken('a-token'), 'the-calendar', self::event());
    }

    /**
     * Google answers the refresh, then each call to the calendar in turn.
     */
    private function calendar(MockResponse ...$answers): GoogleCalendar
    {
        $google = new MockHttpClient(function (string $method, string $url, array $options) use (&$answers): MockResponse {
            if (self::TOKEN_URL === $url) {
                parse_str($options['body'], $form);
                $this->assertSame(['grant_type' => 'refresh_token', 'refresh_token' => 'a-token', 'client_id' => 'an-id', 'client_secret' => 'a-secret'], $form);

                return new JsonMockResponse(['access_token' => 'an-access-token']);
            }

            $this->assertContains('Authorization: Bearer an-access-token', $options['headers']);
            $this->calls[] = [$method, $url, isset($options['body']) && '' !== $options['body'] ? json_decode($options['body'], true) : null];

            return array_shift($answers) ?? throw new \LogicException(sprintf('No answer left for %s %s', $method, $url));
        });

        return $this->calendarOn($google);
    }

    private function calendarOn(MockHttpClient $google): GoogleCalendar
    {
        $endpoints = new GoogleEndpoints('https://front.test/settings/connectors/google', 'https://accounts.google.test/auth', self::TOKEN_URL, 'https://oauth2.google.test/revoke', self::CALENDAR_URL);

        return new GoogleCalendar($google, $endpoints, new GoogleTokens($google, $endpoints), 'https://front.test');
    }

    private static function event(?int $reminder = null): CalendarEvent
    {
        return new CalendarEvent(new ExchangeId(self::EXCHANGE), Channel::VideoCall, new \DateTimeImmutable('2026-11-17 13:00 UTC'), new \DateTimeImmutable('2026-11-17 13:30 UTC'), 'https://meet.google.com/abc-defg-hij', new PersonId(self::CONTACT), 'Ada Lovelace', new OrganizationId(self::ORGANIZATION), 'ACME', $reminder);
    }

    private static function eventId(): string
    {
        return 'p'.str_replace('-', '', self::EXCHANGE);
    }

    private static function eventUrl(): string
    {
        return self::CALENDAR_URL.'/calendars/the-calendar/events/'.self::eventId().'?sendUpdates=none';
    }

    /**
     * @param list<array{string, string, mixed}> $calls
     *
     * @return list<array{string, string}>
     */
    private static function methodsAndUrls(array $calls): array
    {
        return array_map(static fn (array $call): array => [$call[0], $call[1]], $calls);
    }

    private static function client(): ClientCredentials
    {
        return new ClientCredentials(Provider::Google, 'an-id', 'a-secret');
    }
}

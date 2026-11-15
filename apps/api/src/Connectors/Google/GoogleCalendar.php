<?php

namespace App\Connectors\Google;

use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\CalendarGone;
use Prospecter\Core\Connectors\Domain\Exception\CalendarRefusedThePush;
use Prospecter\Core\Connectors\Domain\Model\CalendarEvent;
use Prospecter\Core\Connectors\Domain\Model\Channel;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Port\ProviderCalendar;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The calendar « Prospecter » of a member in Google Calendar, as the spike #92 established it (ADR 0127): the id of
 * an event `p` and the id of its exchange without its dashes (base32hex), put first with `status: confirmed`, which
 * brings back an event removed, created with its id when Google does not know it, put again when it was created
 * meanwhile; no guest, nobody told. The words of the event are those of the member: the channel, the contact, the
 * organization, the link to the card in the SPA.
 *
 * @see https://developers.google.com/calendar/api/v3/reference/events
 */
final readonly class GoogleCalendar implements ProviderCalendar
{
    private const string TIME_ZONE = 'Europe/Paris';
    private const string CALENDAR = 'Prospecter';
    private const int NOT_FOUND = 404;
    private const int GONE = 410;
    private const int CONFLICT = 409;
    private const int UNAUTHORIZED = 401;
    private const int FORBIDDEN = 403;
    private const int TOO_MANY_REQUESTS = 429;
    private const int SERVER_ERROR = 500;
    /** the refusals of a 403 that pass once the quota of Google is back */
    private const array RATE_LIMITS = ['rateLimitExceeded', 'userRateLimitExceeded'];
    private const array CHANNELS = [Channel::Call->value => 'Appel', Channel::Meeting->value => 'Rendez-vous', Channel::VideoCall->value => 'Visio'];
    private const float TIMEOUT = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private GoogleEndpoints $endpoints,
        private GoogleTokens $tokens,
        #[Autowire(env: 'APP_SPA_URL')]
        private string $spaUrl,
    ) {
    }

    public function create(ClientCredentials $client, RefreshToken $refreshToken): string
    {
        $response = $this->call($client, $refreshToken, 'POST', '/calendars', ['summary' => self::CALENDAR, 'timeZone' => self::TIME_ZONE]);
        $this->ensureDone($response, $client);

        return $response->toArray()['id'];
    }

    public function put(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, CalendarEvent $event): void
    {
        $accessToken = $this->tokens->accessTokenFor($client, $refreshToken);
        $body = $this->bodyOf($event);
        $response = $this->request($accessToken, 'PUT', self::eventPath($calendar, $event->exchange), $body);

        if (self::NOT_FOUND === $response->getStatusCode()) {
            $response = $this->request($accessToken, 'POST', sprintf('/calendars/%s/events?sendUpdates=none', rawurlencode($calendar)), $body);
        }

        if (self::CONFLICT === $response->getStatusCode()) {
            $response = $this->request($accessToken, 'PUT', self::eventPath($calendar, $event->exchange), $body);
        }

        if (self::NOT_FOUND === $response->getStatusCode()) {
            throw CalendarGone::identifiedBy($calendar);
        }

        $this->ensureDone($response, $client);
    }

    public function remove(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, ExchangeId $exchange): void
    {
        $response = $this->call($client, $refreshToken, 'DELETE', self::eventPath($calendar, $exchange));

        if (!in_array($response->getStatusCode(), [self::NOT_FOUND, self::GONE], true)) {
            $this->ensureDone($response, $client);
        }
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function call(ClientCredentials $client, RefreshToken $refreshToken, string $method, string $path, ?array $body = null): ResponseInterface
    {
        return $this->request($this->tokens->accessTokenFor($client, $refreshToken), $method, $path, $body);
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function request(string $accessToken, string $method, string $path, ?array $body = null): ResponseInterface
    {
        return $this->httpClient->request($method, $this->endpoints->calendarUrl.$path, [
            'headers' => ['Authorization' => 'Bearer '.$accessToken],
            'timeout' => self::TIMEOUT,
            ...(null === $body ? [] : ['json' => $body]),
        ]);
    }

    private static function eventPath(string $calendar, ExchangeId $exchange): string
    {
        return sprintf('/calendars/%s/events/%s?sendUpdates=none', rawurlencode($calendar), self::eventIdOf($exchange));
    }

    private static function eventIdOf(ExchangeId $exchange): string
    {
        return 'p'.str_replace('-', '', $exchange->value);
    }

    /**
     * @throws AccessRevoked|CalendarRefusedThePush
     */
    private function ensureDone(ResponseInterface $response, ClientCredentials $client): void
    {
        $status = $response->getStatusCode();

        match (true) {
            $status < 300 => null,
            self::UNAUTHORIZED === $status => throw AccessRevoked::by($client->provider),
            self::TOO_MANY_REQUESTS === $status, $status >= self::SERVER_ERROR, self::FORBIDDEN === $status && self::isARateLimit($response) => throw new \RuntimeException(sprintf('Google answered %d to the calendar, to try again', $status)),
            default => throw CalendarRefusedThePush::by($client->provider, $status),
        };
    }

    private static function isARateLimit(ResponseInterface $response): bool
    {
        $error = json_decode($response->getContent(false), true);
        $reasons = is_array($error) ? array_column($error['error']['errors'] ?? [], 'reason') : [];

        return [] !== array_intersect(self::RATE_LIMITS, $reasons);
    }

    /**
     * @return array<string, mixed>
     */
    private function bodyOf(CalendarEvent $event): array
    {
        return [
            'id' => self::eventIdOf($event->exchange),
            'status' => 'confirmed',
            'summary' => self::summaryOf($event),
            ...(null === $event->venue ? [] : ['location' => $event->venue]),
            'description' => $this->cardOf($event),
            'start' => self::momentOf($event->startsAt),
            'end' => self::momentOf($event->endsAt),
            'reminders' => ['useDefault' => false, 'overrides' => null === $event->reminderMinutesBefore ? [] : [['method' => 'popup', 'minutes' => $event->reminderMinutesBefore]]],
        ];
    }

    /**
     * « Visio · Ada Lovelace (ACME) », or « Rendez-vous · ACME » with the organization itself.
     */
    private static function summaryOf(CalendarEvent $event): string
    {
        $channel = self::CHANNELS[$event->channel->value];

        return null === $event->contactName
            ? sprintf('%s · %s', $channel, $event->organizationName)
            : sprintf('%s · %s (%s)', $channel, $event->contactName, $event->organizationName);
    }

    private function cardOf(CalendarEvent $event): string
    {
        return null === $event->contact
            ? sprintf('%s/organizations/%s', $this->spaUrl, $event->organization->value)
            : sprintf('%s/contacts/%s', $this->spaUrl, $event->contact->value);
    }

    /**
     * @return array{dateTime: string, timeZone: string}
     */
    private static function momentOf(\DateTimeImmutable $moment): array
    {
        return ['dateTime' => $moment->setTimezone(new \DateTimeZone(self::TIME_ZONE))->format(\DATE_ATOM), 'timeZone' => self::TIME_ZONE];
    }
}

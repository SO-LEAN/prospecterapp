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
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The calendar « Prospecter » of a member in Google Calendar, as the spike #92 established it (ADR 0127): the id of
 * an event `p` and the id of its exchange without its dashes (base32hex), put first with `status: confirmed`, which
 * brings back an event removed, created with its id when Google does not know it, put again when it was created
 * meanwhile; nobody told, but the guest of an event, the booker of an exchange booked by the public link (ADR 0175),
 * told of its creation, its changes and its cancellation. The words of the event are those of the member: the
 * channel, the contact, the organization, the link to the card in the SPA, which an event inviting a guest does not
 * show them, saying instead who calls them, and at which number, for a call. A video call asking a link is put with
 * the request of a Meet conference, its link answered at once (ADR 0137).
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
    /** without it, Google ignores the conference of the body and keeps that of the event as it is */
    private const string WITH_CONFERENCE = '&conferenceDataVersion=1';
    private const string MEET = 'hangoutsMeet';
    private const string TELLS_NOBODY = 'none';
    private const string TELLS_THE_GUESTS = 'all';

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

    public function put(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, CalendarEvent $event): ?string
    {
        $accessToken = $this->tokens->accessTokenFor($client, $refreshToken);
        $body = $this->bodyOf($event);
        $conference = $event->createsALink ? self::WITH_CONFERENCE : '';
        $updates = self::updatesOf($event);
        $response = $this->request($accessToken, 'PUT', self::eventPath($calendar, $event->exchange, $updates).$conference, $body);

        if (self::NOT_FOUND === $response->getStatusCode()) {
            $response = $this->request($accessToken, 'POST', sprintf('/calendars/%s/events?sendUpdates=%s', rawurlencode($calendar), $updates).$conference, $body);
        }

        if (self::CONFLICT === $response->getStatusCode()) {
            $response = $this->request($accessToken, 'PUT', self::eventPath($calendar, $event->exchange, $updates).$conference, $body);
        }

        if (self::NOT_FOUND === $response->getStatusCode()) {
            throw CalendarGone::identifiedBy($calendar);
        }

        $this->ensureDone($response, $client);

        return $event->createsALink ? self::conferenceLinkOf($response) : null;
    }

    /**
     * The link of the conference Google created for the event, at once (the spike #92).
     */
    private static function conferenceLinkOf(ResponseInterface $response): ?string
    {
        $link = $response->toArray(false)['hangoutLink'] ?? null;

        return is_string($link) ? $link : null;
    }

    public function remove(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, ExchangeId $exchange): void
    {
        $this->delete($client, $refreshToken, self::eventPath($calendar, $exchange));
    }

    public function cancel(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, ExchangeId $exchange): void
    {
        $this->delete($client, $refreshToken, self::eventPath($calendar, $exchange, self::TELLS_THE_GUESTS));
    }

    /**
     * An event Google no longer has is withdrawn already.
     */
    private function delete(ClientCredentials $client, RefreshToken $refreshToken, string $eventPath): void
    {
        $response = $this->call($client, $refreshToken, 'DELETE', $eventPath);

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

    /**
     * Google tells the guest of an event, and nobody else (ADR 0175).
     */
    private static function updatesOf(CalendarEvent $event): string
    {
        return null === $event->guest ? self::TELLS_NOBODY : self::TELLS_THE_GUESTS;
    }

    private static function eventPath(string $calendar, ExchangeId $exchange, string $updates = self::TELLS_NOBODY): string
    {
        return sprintf('/calendars/%s/events/%s?sendUpdates=%s', rawurlencode($calendar), self::eventIdOf($exchange), $updates);
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
        $description = $this->descriptionOf($event);

        return [
            'id' => self::eventIdOf($event->exchange),
            'status' => 'confirmed',
            'summary' => self::summaryOf($event),
            ...(null === $event->venue ? [] : ['location' => $event->venue]),
            ...(null === $description ? [] : ['description' => $description]),
            'start' => self::momentOf($event->startsAt),
            'end' => self::momentOf($event->endsAt),
            'reminders' => ['useDefault' => false, 'overrides' => null === $event->reminderMinutesBefore ? [] : [['method' => 'popup', 'minutes' => $event->reminderMinutesBefore]]],
            ...($event->createsALink ? ['conferenceData' => self::conferenceRequestOf($event->exchange)] : []),
            ...(null === $event->guest ? [] : ['attendees' => [['email' => $event->guest->email->value]]]),
        ];
    }

    /**
     * Identified by its exchange: a request Google already answered is ignored, the conference created once, however
     * many times its event is pushed (ADR 0137).
     *
     * @return array{createRequest: array{requestId: string, conferenceSolutionKey: array{type: string}}}
     */
    private static function conferenceRequestOf(ExchangeId $exchange): array
    {
        return ['createRequest' => ['requestId' => $exchange->value, 'conferenceSolutionKey' => ['type' => self::MEET]]];
    }

    /**
     * « Visio · Ada Lovelace (ACME) », « Rendez-vous · ACME » with the organization itself, or « Visio · Ada
     * Lovelace » with a contact to attach.
     */
    private static function summaryOf(CalendarEvent $event): string
    {
        $channel = self::CHANNELS[$event->channel->value];

        return match (true) {
            null === $event->contactName => sprintf('%s · %s', $channel, $event->organizationName),
            null === $event->organizationName => sprintf('%s · %s', $channel, $event->contactName),
            default => sprintf('%s · %s (%s)', $channel, $event->contactName, $event->organizationName),
        };
    }

    /**
     * The card it leads to in Prospecter, for the member; an event inviting a guest shows them none, but for a call
     * who calls them, and at which number (ADR 0175).
     */
    private function descriptionOf(CalendarEvent $event): ?string
    {
        $guest = $event->guest;
        if (null === $guest) {
            return $this->cardOf($event);
        }

        return null === $guest->calledAt ? null : self::callOf($guest->calledAt, $guest->calledBy);
    }

    /**
     * « Alan vous appellera au +33 6 12 34 56 78. », without the first name of a host who gave none.
     */
    private static function callOf(PhoneNumber $number, ?string $caller): string
    {
        return null === $caller
            ? sprintf('Vous serez appelé au %s.', $number->international())
            : sprintf('%s vous appellera au %s.', $caller, $number->international());
    }

    private function cardOf(CalendarEvent $event): string
    {
        return null === $event->contact
            ? sprintf('%s/organizations/%s', $this->spaUrl, $event->organization?->value)
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

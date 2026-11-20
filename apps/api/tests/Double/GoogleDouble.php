<?php

namespace Tests\App\Double;

use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The OAuth 2 endpoints of Google as the tests see them (ADR 0092, 0120): its token endpoint refuses every call
 * without the id of a client and every code but the one a test makes it grant, grants every refresh until a test
 * makes it refuse them or the token was revoked, and its revocation keeps the tokens it revoked; its calendars keep the
 * events put into them (ADR 0127), an event Google does not know answered 404 to a `PUT`, created by a `POST` with its
 * id; its mailbox keeps the messages a test makes it receive, its history 1000 and one point per message (ADR 0154);
 * out of reach, failing or limiting the requests once a test says so. Not reset between the requests of a client that does not reboot, so that a
 * test arranges it before its gestures.
 */
final class GoogleDouble
{
    private const string CALENDAR_OF_PROSPECTER = 'https://www.googleapis.com/auth/calendar.app.created';
    private const string GMAIL = 'https://www.googleapis.com/auth/gmail.readonly';
    private const string WATCHED_UNTIL = '2026-10-22 09:00 UTC';
    /** the messages arrive just before the moment of the tests */
    private const string RECEIVED_AT = '2026-10-15 08:30 UTC';

    /** @var array{code: string, email: string, scope: string, refreshToken: string}|null */
    private ?array $grant = null;
    private bool $refusesTheTokens = false;
    private bool $answers = true;
    /** the status Google answers every call with while it fails or limits them */
    private ?int $failure = null;
    private bool $refusesTheEvents = false;
    /** @var list<string> */
    private array $revoked = [];
    /** @var array{string, string}|null the id and the secret of the client the last token was asked by */
    private ?array $lastClient = null;
    /** @var array<string, array<string, array<string, mixed>>> the events of each calendar, by their id */
    private array $calendars = [];
    /** @var list<string> the topic of each watch of a mailbox asked, the latest last */
    private array $watches = [];
    private int $stops = 0;
    private bool $failsTheNextWatch = false;
    /** @var list<array{id: string, from: string, subject: string, text: string}> the messages the mailbox received, the oldest first */
    private array $messages = [];
    /** @var list<array{kid: string, n: string, e: string}> the keys Google publishes, its tokens signed by one of them */
    private array $keys = [];

    public function __construct(
        private readonly string $tokenUrl,
        private readonly string $revokeUrl,
        private readonly string $calendarUrl,
        private readonly string $gmailUrl,
        private readonly string $certsUrl,
    ) {
    }

    public function grants(string $code, string $email, string $refreshToken, bool $withTheCalendar = true): void
    {
        $this->grant = ['code' => $code, 'email' => $email, 'scope' => 'openid https://www.googleapis.com/auth/userinfo.email'.($withTheCalendar ? ' '.self::CALENDAR_OF_PROSPECTER : ''), 'refreshToken' => $refreshToken];
    }

    /**
     * The calendar and the reading of the mailbox granted for the code (ADR 0154).
     */
    public function grantsGmail(string $code, string $email, string $refreshToken): void
    {
        $this->grant = ['code' => $code, 'email' => $email, 'scope' => 'openid https://www.googleapis.com/auth/userinfo.email '.self::CALENDAR_OF_PROSPECTER.' '.self::GMAIL, 'refreshToken' => $refreshToken];
    }

    public function refusesTheTokens(): void
    {
        $this->refusesTheTokens = true;
    }

    /**
     * Every event pushed refused for good, as a 400 of Google, until it accepts them again (ADR 0133).
     */
    public function refusesTheEvents(): void
    {
        $this->refusesTheEvents = true;
    }

    public function acceptsTheEvents(): void
    {
        $this->refusesTheEvents = false;
    }

    /**
     * Google out of reach: every call fails before any answer, as a timeout does.
     */
    public function doesNotAnswer(): void
    {
        $this->answers = false;
    }

    /**
     * Google failing on its side: every call answered by an error of its own, a 503.
     */
    public function fails(): void
    {
        $this->failure = 503;
    }

    /**
     * Google limiting the requests: every call answered 429, too many requests.
     */
    public function limits(): void
    {
        $this->failure = 429;
    }

    /**
     * @return list<string>
     */
    public function revoked(): array
    {
        return $this->revoked;
    }

    /**
     * @return array{string, string}|null
     */
    public function clientOfTheLastToken(): ?array
    {
        return $this->lastClient;
    }

    /**
     * @return array<string, array<string, mixed>> the events of every calendar, by their id
     */
    public function events(): array
    {
        return array_merge([], ...array_values($this->calendars));
    }

    public function serves(string $url): bool
    {
        return str_starts_with($url, $this->tokenUrl) || str_starts_with($url, $this->revokeUrl) || str_starts_with($url, $this->calendarUrl) || str_starts_with($url, $this->gmailUrl) || $url === $this->certsUrl;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function answer(string $method, string $url, array $options): ResponseInterface
    {
        return match (true) {
            !$this->answers => new MockResponse('', ['error' => 'Google does not answer']),
            null !== $this->failure => new MockResponse('', ['http_code' => $this->failure]),
            str_starts_with($url, $this->calendarUrl) => $this->calendar($method, substr($url, strlen($this->calendarUrl)), $options),
            $url === $this->certsUrl => new JsonMockResponse(['keys' => array_map(static fn (array $key): array => ['kty' => 'RSA', 'alg' => 'RS256', 'use' => 'sig', ...$key], $this->keys)]),
            str_starts_with($url, $this->gmailUrl) => $this->gmail(substr($url, strlen($this->gmailUrl)), $options),
            str_starts_with($url, $this->tokenUrl) => $this->token(self::formOf($options)),
            default => $this->revoke(self::formOf($options)),
        };
    }

    /**
     * The public key of a pair a test drew, its modulus and its exponent in base64url, published among the keys of
     * Google its tokens are checked with (ADR 0154).
     */
    public function publishesTheKey(string $kid, string $modulus, string $exponent): void
    {
        $this->keys[] = ['kid' => $kid, 'n' => $modulus, 'e' => $exponent];
    }

    /**
     * @return list<string> the topic of each watch asked, the latest last
     */
    public function watches(): array
    {
        return $this->watches;
    }

    /**
     * The next watch of a mailbox answered by an error of Google, a 500, the others as usual.
     */
    public function failsTheNextWatch(): void
    {
        $this->failsTheNextWatch = true;
    }

    /**
     * A message arrives in the mailbox watched, its history one point further than 1000 (ADR 0154).
     */
    public function receives(string $id, string $from, string $subject, string $text): void
    {
        $this->messages[] = ['id' => $id, 'from' => $from, 'subject' => $subject, 'text' => $text];
    }

    public function stops(): int
    {
        return $this->stops;
    }

    /**
     * A mailbox watched for a week from the moment of the tests, standing at the history 1000 (ADR 0154).
     *
     * @param array<string, mixed> $options
     */
    private function gmail(string $path, array $options): ResponseInterface
    {
        $read = $this->read((string) parse_url($path, \PHP_URL_PATH), (string) parse_url($path, \PHP_URL_QUERY));
        if (null !== $read) {
            return $read;
        }

        if ('/users/me/stop' === $path) {
            ++$this->stops;

            return new MockResponse('', ['http_code' => 204]);
        }

        if ($this->failsTheNextWatch) {
            $this->failsTheNextWatch = false;

            return new MockResponse('', ['http_code' => 500]);
        }

        $this->watches[] = (string) (json_decode((string) ($options['body'] ?? ''), true)['topicName'] ?? '');

        return new JsonMockResponse(['historyId' => '1000', 'expiration' => (string) (new \DateTimeImmutable(self::WATCHED_UNTIL)->getTimestamp() * 1000)]);
    }

    /**
     * The history of the mailbox, its profile, the list of its messages and each of them, as Gmail answers them.
     */
    private function read(string $path, string $query): ?ResponseInterface
    {
        parse_str($query, $parameters);
        $historyId = (string) (1000 + count($this->messages));

        return match (true) {
            '/users/me/history' === $path => new JsonMockResponse(['history' => array_values(array_filter(array_map(static fn (int $position, array $message): array => ['id' => (string) (1001 + $position), 'messagesAdded' => [['message' => ['id' => $message['id']]]]], array_keys($this->messages), $this->messages), static fn (array $record): bool => (int) $record['id'] > (int) ($parameters['startHistoryId'] ?? 0))), 'historyId' => $historyId]),
            '/users/me/profile' === $path => new JsonMockResponse(['historyId' => $historyId]),
            '/users/me/messages' === $path => new JsonMockResponse(['messages' => array_map(static fn (array $message): array => ['id' => $message['id']], array_reverse($this->messages))]),
            str_starts_with($path, '/users/me/messages/') => $this->message(substr($path, strlen('/users/me/messages/'))),
            default => null,
        };
    }

    private function message(string $id): ResponseInterface
    {
        $message = array_find($this->messages, static fn (array $message): bool => $message['id'] === $id);

        return null === $message ? new MockResponse('', ['http_code' => 404]) : new JsonMockResponse([
            'id' => $id,
            'labelIds' => ['INBOX', 'UNREAD'],
            'internalDate' => (string) (new \DateTimeImmutable(self::RECEIVED_AT)->getTimestamp() * 1000),
            'payload' => [
                'mimeType' => 'text/plain',
                'headers' => [['name' => 'From', 'value' => $message['from']], ['name' => 'Subject', 'value' => $message['subject']], ['name' => 'Message-ID', 'value' => sprintf('<%s@mail.test>', $id)]],
                'body' => ['data' => rtrim(strtr(base64_encode($message['text']), '+/', '-_'), '=')],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function calendar(string $method, string $address, array $options): ResponseInterface
    {
        $body = is_string($options['body'] ?? null) && '' !== $options['body'] ? (array) json_decode($options['body'], true) : [];
        $segments = explode('/', trim((string) parse_url($address, \PHP_URL_PATH), '/'));
        parse_str((string) parse_url($address, \PHP_URL_QUERY), $query);
        $body = '1' === ($query['conferenceDataVersion'] ?? null) ? $body : array_diff_key($body, ['conferenceData' => true]);
        $calendar = $segments[1] ?? '';
        $event = $segments[3] ?? null;

        if ('POST' === $method && ['calendars'] === $segments) {
            $id = sprintf('calendar-of-the-double-%d', count($this->calendars) + 1);
            $this->calendars[$id] = [];

            return new JsonMockResponse(['id' => $id]);
        }

        if (!isset($this->calendars[$calendar])) {
            return new MockResponse('', ['http_code' => 404]);
        }

        return match ($method) {
            'PUT' => isset($this->calendars[$calendar][$event]) ? $this->put($calendar, (string) $event, $body) : new MockResponse('', ['http_code' => 404]),
            'POST' => $this->put($calendar, (string) ($body['id'] ?? ''), $body),
            'DELETE' => $this->delete($calendar, (string) $event),
            default => new MockResponse('', ['http_code' => 405]),
        };
    }

    /**
     * The conference of the event kept, created once for the request of one (the spike #92): its link answered in
     * `hangoutLink`.
     *
     * @param array<string, mixed> $event
     */
    private function put(string $calendar, string $id, array $event): ResponseInterface
    {
        if ($this->refusesTheEvents) {
            return new JsonMockResponse(['error' => ['code' => 400, 'message' => 'Invalid event']], ['http_code' => 400]);
        }

        $conference = $this->calendars[$calendar][$id]['hangoutLink'] ?? self::conferenceAskedBy($event);
        $this->calendars[$calendar][$id] = [...array_diff_key($event, ['conferenceData' => true]), ...(null === $conference ? [] : ['hangoutLink' => $conference])];

        return new JsonMockResponse($this->calendars[$calendar][$id]);
    }

    /**
     * @param array<string, mixed> $event
     */
    private static function conferenceAskedBy(array $event): ?string
    {
        $requestId = $event['conferenceData']['createRequest']['requestId'] ?? null;

        return is_string($requestId) ? sprintf('https://meet.google.com/%s', substr(md5($requestId), 0, 10)) : null;
    }

    private function delete(string $calendar, string $event): ResponseInterface
    {
        if (!isset($this->calendars[$calendar][$event])) {
            return new MockResponse('', ['http_code' => 410]);
        }

        unset($this->calendars[$calendar][$event]);

        return new MockResponse('', ['http_code' => 204]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, string>
     */
    private static function formOf(array $options): array
    {
        $body = $options['body'] ?? '';
        parse_str(is_string($body) ? $body : http_build_query($body), $form);

        return array_filter($form, is_string(...));
    }

    /**
     * @param array<string, string> $form
     */
    private function token(array $form): ResponseInterface
    {
        if ('' === ($form['client_id'] ?? '')) {
            return new JsonMockResponse(['error' => 'invalid_client'], ['http_code' => 401]);
        }

        $this->lastClient = [$form['client_id'], $form['client_secret'] ?? ''];

        return match ($form['grant_type'] ?? null) {
            'authorization_code' => $this->exchange($form['code'] ?? ''),
            'refresh_token' => $this->refuses($form['refresh_token'] ?? '') ? self::invalidGrant() : new JsonMockResponse(['access_token' => 'an-access-token']),
            default => self::invalidGrant(),
        };
    }

    private function exchange(string $code): ResponseInterface
    {
        if (null === $this->grant || $this->grant['code'] !== $code) {
            return self::invalidGrant();
        }

        return new JsonMockResponse([
            'access_token' => 'an-access-token',
            'refresh_token' => $this->grant['refreshToken'],
            'scope' => $this->grant['scope'],
            'id_token' => self::idTokenOf($this->grant['email']),
        ]);
    }

    private function refuses(string $refreshToken): bool
    {
        return $this->refusesTheTokens || in_array($refreshToken, $this->revoked, true);
    }

    private static function idTokenOf(string $email): string
    {
        $part = static fn (array $claims): string => rtrim(strtr(base64_encode(json_encode($claims, \JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        return $part(['alg' => 'RS256']).'.'.$part(['email' => $email]).'.signature';
    }

    private static function invalidGrant(): JsonMockResponse
    {
        return new JsonMockResponse(['error' => 'invalid_grant'], ['http_code' => 400]);
    }

    /**
     * @param array<string, string> $form
     */
    private function revoke(array $form): ResponseInterface
    {
        $this->revoked[] = $form['token'] ?? '';

        return new JsonMockResponse([]);
    }
}

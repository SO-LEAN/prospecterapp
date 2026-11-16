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
 * id; out of reach once a test says so. Not reset between the requests of a client that does not reboot, so that a
 * test arranges it before its gestures.
 */
final class GoogleDouble
{
    private const string CALENDAR_OF_PROSPECTER = 'https://www.googleapis.com/auth/calendar.app.created';

    /** @var array{code: string, email: string, scope: string, refreshToken: string}|null */
    private ?array $grant = null;
    private bool $refusesTheTokens = false;
    private bool $answers = true;
    /** @var list<string> */
    private array $revoked = [];
    /** @var array{string, string}|null the id and the secret of the client the last token was asked by */
    private ?array $lastClient = null;
    /** @var array<string, array<string, array<string, mixed>>> the events of each calendar, by their id */
    private array $calendars = [];

    public function __construct(
        private readonly string $tokenUrl,
        private readonly string $revokeUrl,
        private readonly string $calendarUrl,
    ) {
    }

    public function grants(string $code, string $email, string $refreshToken, bool $withTheCalendar = true): void
    {
        $this->grant = ['code' => $code, 'email' => $email, 'scope' => 'openid https://www.googleapis.com/auth/userinfo.email'.($withTheCalendar ? ' '.self::CALENDAR_OF_PROSPECTER : ''), 'refreshToken' => $refreshToken];
    }

    public function refusesTheTokens(): void
    {
        $this->refusesTheTokens = true;
    }

    /**
     * Google out of reach: every call fails before any answer, as a timeout does.
     */
    public function doesNotAnswer(): void
    {
        $this->answers = false;
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
        return str_starts_with($url, $this->tokenUrl) || str_starts_with($url, $this->revokeUrl) || str_starts_with($url, $this->calendarUrl);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function answer(string $method, string $url, array $options): ResponseInterface
    {
        return match (true) {
            !$this->answers => new MockResponse('', ['error' => 'Google does not answer']),
            str_starts_with($url, $this->calendarUrl) => $this->calendar($method, (string) parse_url(substr($url, strlen($this->calendarUrl)), \PHP_URL_PATH), $options),
            str_starts_with($url, $this->tokenUrl) => $this->token(self::formOf($options)),
            default => $this->revoke(self::formOf($options)),
        };
    }

    /**
     * @param array<string, mixed> $options
     */
    private function calendar(string $method, string $path, array $options): ResponseInterface
    {
        $body = is_string($options['body'] ?? null) && '' !== $options['body'] ? (array) json_decode($options['body'], true) : [];
        $segments = explode('/', trim($path, '/'));
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
     * @param array<string, mixed> $event
     */
    private function put(string $calendar, string $id, array $event): ResponseInterface
    {
        $this->calendars[$calendar][$id] = $event;

        return new JsonMockResponse($event);
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

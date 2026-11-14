<?php

namespace Tests\App\Double;

use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The OAuth 2 endpoints of Google as the tests see them (ADR 0092, 0120): its token endpoint refuses every code but
 * the one a test makes it grant, grants every refresh until a test makes it refuse them or the token was revoked, and
 * its revocation keeps the tokens it revoked. Not reset between the requests of a client that does not reboot, so
 * that a test arranges it before its gestures.
 */
final class GoogleDouble
{
    private const string CALENDAR_OF_PROSPECTER = 'https://www.googleapis.com/auth/calendar.app.created';

    /** @var array{code: string, email: string, scope: string, refreshToken: string}|null */
    private ?array $grant = null;
    private bool $refusesTheTokens = false;
    /** @var list<string> */
    private array $revoked = [];

    public function __construct(
        private readonly string $tokenUrl,
        private readonly string $revokeUrl,
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
     * @return list<string>
     */
    public function revoked(): array
    {
        return $this->revoked;
    }

    public function serves(string $url): bool
    {
        return str_starts_with($url, $this->tokenUrl) || str_starts_with($url, $this->revokeUrl);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function answer(string $url, array $options): ResponseInterface
    {
        $form = self::formOf($options);

        return str_starts_with($url, $this->tokenUrl) ? $this->token($form) : $this->revoke($form);
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

<?php

declare(strict_types=1);

namespace App\Connectors\Google;

use App\Shared\ServiceUnavailable;
use App\Shared\Typed;
use Prospecter\Core\Connectors\Domain\Exception\AccessNotGranted;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\SharedKernel\Domain\Email;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The OAuth 2 endpoints of Google (ADR 0120), with the secret of the client: a code exchanged with its verifier, a
 * refresh token tried, then revoked. Google, the one provider so far.
 *
 * @see https://developers.google.com/identity/protocols/oauth2/web-server
 */
final readonly class GoogleProviderAccess implements ProviderAccess
{
    private const int OK = 200;
    private const int TOO_MANY_REQUESTS = 429;
    private const int SERVER_ERROR = 500;
    /** what Google answers for a token revoked, expired or replaced too many times */
    private const string INVALID_GRANT = 'invalid_grant';
    private const float TIMEOUT = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private GoogleEndpoints $endpoints,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ServiceUnavailable when Google does not answer, fails or limits the requests: it refused nothing then
     */
    public function exchange(ClientCredentials $client, string $code, string $codeVerifier): Grant
    {
        $response = $this->token($client, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'code_verifier' => $codeVerifier,
            'redirect_uri' => $this->endpoints->redirectUri,
        ]);

        if (self::OK !== self::statusOf($response)) {
            throw AccessNotGranted::by($client->provider);
        }

        return self::grantOf(self::tokensIn($response), $client->provider);
    }

    /**
     * @return array<string, mixed>
     */
    private static function tokensIn(ResponseInterface $response): array
    {
        try {
            return Typed::document($response->toArray());
        } catch (TransportExceptionInterface $failure) {
            throw ServiceUnavailable::google($failure);
        }
    }

    /**
     * @param array<string, mixed> $tokens
     */
    private static function grantOf(array $tokens, Provider $provider): Grant
    {
        if (!isset($tokens['refresh_token'], $tokens['id_token']) || '' === $tokens['refresh_token']) {
            throw AccessNotGranted::by($provider);
        }

        return new Grant(
            new Email(self::emailIn(Typed::string($tokens['id_token']))),
            new RefreshToken(Typed::string($tokens['refresh_token'])),
            ...self::scopesIn(Typed::string($tokens['scope'] ?? '')),
        );
    }

    /**
     * @return list<Scope> what Prospecter asked among the scopes Google granted
     */
    private static function scopesIn(string $granted): array
    {
        $scopesOfGoogle = explode(' ', $granted);

        return array_values(array_filter(Scope::cases(), static fn (Scope $scope): bool => in_array(GoogleAuthorization::SCOPES_OF_GOOGLE[$scope->value], $scopesOfGoogle, true)));
    }

    /**
     * The identity token comes straight from the token endpoint over TLS: its claims are read without checking its
     * signature, as Google allows for a token received so.
     */
    private static function emailIn(string $idToken): string
    {
        $claims = json_decode(base64_decode(strtr(explode('.', $idToken)[1] ?? '', '-_', '+/')), true);

        return is_array($claims) && is_string($claims['email'] ?? null) ? $claims['email'] : throw new \UnexpectedValueException('Google gave an identity token without email');
    }

    /**
     * @throws ServiceUnavailable when Google does not answer, fails or limits the requests: nothing is known of the access then
     */
    public function stillGrants(ClientCredentials $client, RefreshToken $refreshToken): bool
    {
        $response = $this->token($client, ['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken->value]);
        $status = self::statusOf($response);

        if (self::OK === $status) {
            return true;
        }

        return self::isInvalidGrant($response) ? false : throw new \RuntimeException(sprintf('Google answered %d to a refresh', $status));
    }

    private static function statusOf(ResponseInterface $response): int
    {
        try {
            $status = $response->getStatusCode();
        } catch (TransportExceptionInterface $failure) {
            throw ServiceUnavailable::google($failure);
        }

        return self::isOutOfReach($status) ? throw ServiceUnavailable::google(new \RuntimeException(sprintf('Google answered %d', $status))) : $status;
    }

    private static function isOutOfReach(int $status): bool
    {
        return self::TOO_MANY_REQUESTS === $status || $status >= self::SERVER_ERROR;
    }

    private static function isInvalidGrant(ResponseInterface $response): bool
    {
        $error = json_decode($response->getContent(false), true);

        return is_array($error) && self::INVALID_GRANT === ($error['error'] ?? null);
    }

    public function revoke(Provider $provider, RefreshToken $refreshToken): void
    {
        try {
            $response = $this->httpClient->request('POST', $this->endpoints->revokeUrl, ['body' => ['token' => $refreshToken->value], 'timeout' => self::TIMEOUT]);
            if (self::OK !== $response->getStatusCode()) {
                $this->logger->warning('Google refused to revoke an access: it is forgotten all the same', ['status' => $response->getStatusCode()]);
            }
        } catch (ExceptionInterface $failure) {
            $this->logger->warning('An access to Google could not be revoked: it is forgotten all the same', ['exception' => $failure]);
        }
    }

    /**
     * @param array<string, string> $grant
     */
    private function token(ClientCredentials $client, array $grant): ResponseInterface
    {
        return $this->httpClient->request('POST', $this->endpoints->tokenUrl, [
            'body' => [...$grant, 'client_id' => $client->id, 'client_secret' => $client->secret],
            'timeout' => self::TIMEOUT,
        ]);
    }
}

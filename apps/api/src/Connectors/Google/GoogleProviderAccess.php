<?php

namespace App\Connectors\Google;

use Prospecter\Core\Connectors\Domain\Exception\AccessNotGranted;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\SharedKernel\Domain\Email;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
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
    /** what Google answers for a token revoked, expired or replaced too many times */
    private const string INVALID_GRANT = 'invalid_grant';
    private const float TIMEOUT = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private GoogleEndpoints $endpoints,
        private LoggerInterface $logger,
    ) {
    }

    public function exchange(ClientCredentials $client, string $code, string $codeVerifier): Grant
    {
        $response = $this->token($client, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'code_verifier' => $codeVerifier,
            'redirect_uri' => $this->endpoints->redirectUri,
        ]);

        if (self::OK !== $response->getStatusCode()) {
            throw AccessNotGranted::by($client->provider);
        }

        return self::grantOf($response->toArray(), $client->provider);
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
            new Email(self::emailIn($tokens['id_token'])),
            new RefreshToken($tokens['refresh_token']),
            in_array(GoogleAuthorization::CALENDAR_SCOPE, explode(' ', $tokens['scope'] ?? ''), true),
        );
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

    public function stillGrants(ClientCredentials $client, RefreshToken $refreshToken): bool
    {
        $response = $this->token($client, ['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken->value]);

        if (self::OK === $response->getStatusCode()) {
            return true;
        }

        return self::isInvalidGrant($response) ? false : throw new \RuntimeException(sprintf('Google answered %d to a refresh', $response->getStatusCode()));
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

<?php

declare(strict_types=1);

namespace App\Connectors\Google;

use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The one-hour access token that Google gives for a member's refresh token, through their account's client. Each push
 * asks for one, and it is never kept (ADR 0120, 0127).
 */
final readonly class GoogleTokens
{
    private const int OK = 200;
    /** what Google answers for a token that was revoked, expired or replaced too many times */
    private const string INVALID_GRANT = 'invalid_grant';
    private const float TIMEOUT = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private GoogleEndpoints $endpoints,
    ) {
    }

    /**
     * @throws AccessRevoked
     */
    public function accessTokenFor(ClientCredentials $client, RefreshToken $refreshToken): string
    {
        $response = $this->httpClient->request('POST', $this->endpoints->tokenUrl, [
            'body' => ['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken->value, 'client_id' => $client->id, 'client_secret' => $client->secret],
            'timeout' => self::TIMEOUT,
        ]);
        $decoded = json_decode($response->getContent(false), true);
        $answer = is_array($decoded) ? $decoded : [];

        if (self::OK === $response->getStatusCode() && is_string($answer['access_token'] ?? null)) {
            return $answer['access_token'];
        }

        return self::INVALID_GRANT === ($answer['error'] ?? null) ? throw AccessRevoked::by($client->provider) : throw new \RuntimeException(sprintf('Google answered %d to a refresh', $response->getStatusCode()));
    }
}

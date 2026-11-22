<?php

declare(strict_types=1);

namespace App\Connectors\Google;

use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The access token of an hour Google gives for the refresh token of a member, through the client of their account,
 * asked for each push and never kept (ADR 0120, 0127).
 */
final readonly class GoogleTokens
{
    private const int OK = 200;
    /** what Google answers for a token revoked, expired or replaced too many times */
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
        $answer = json_decode($response->getContent(false), true);

        if (self::OK === $response->getStatusCode() && is_string($answer['access_token'] ?? null)) {
            return $answer['access_token'];
        }

        return self::INVALID_GRANT === ($answer['error'] ?? null) ? throw AccessRevoked::by($client->provider) : throw new \RuntimeException(sprintf('Google answered %d to a refresh', $response->getStatusCode()));
    }
}

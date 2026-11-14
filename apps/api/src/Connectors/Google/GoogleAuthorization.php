<?php

namespace App\Connectors\Google;

/**
 * Draws a new authorization of Google: an unguessable `state`, a verifier of the code and its challenge (PKCE S256),
 * and the address asking the calendar of Prospecter with a lasting access (`offline`, the consent asked each time
 * so that Google gives a refresh token), ADR 0120.
 */
final readonly class GoogleAuthorization
{
    public const string SCOPES = 'openid email '.self::CALENDAR_SCOPE;
    public const string CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar.app.created';
    private const int STATE_BYTES = 16;
    private const int VERIFIER_BYTES = 48;

    public function __construct(private GoogleClient $client)
    {
    }

    public function newRequest(): AuthorizationRequest
    {
        $state = bin2hex(random_bytes(self::STATE_BYTES));
        $codeVerifier = self::base64Url(random_bytes(self::VERIFIER_BYTES));

        return new AuthorizationRequest($state, $codeVerifier, $this->client->authorizationUrl.'?'.http_build_query([
            'client_id' => $this->client->id,
            'redirect_uri' => $this->client->redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
            'code_challenge' => self::base64Url(hash('sha256', $codeVerifier, true)),
            'code_challenge_method' => 'S256',
        ], encoding_type: \PHP_QUERY_RFC3986));
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}

<?php

namespace App\Connectors\Google;

/**
 * Draws a new authorization of Google: an unguessable `state` and a verifier of the code; then the address asking,
 * for the client of the account of the member (ADR 0125), the calendar of Prospecter with a lasting access
 * (`offline`, the consent asked each time so that Google gives a refresh token) and the challenge of the verifier
 * (PKCE S256), ADR 0120.
 */
final readonly class GoogleAuthorization
{
    public const string SCOPES = 'openid email '.self::CALENDAR_SCOPE;
    public const string CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar.app.created';
    private const int STATE_BYTES = 16;
    private const int VERIFIER_BYTES = 48;

    public function __construct(private GoogleEndpoints $endpoints)
    {
    }

    public function newRequest(): AuthorizationRequest
    {
        return new AuthorizationRequest(bin2hex(random_bytes(self::STATE_BYTES)), self::base64Url(random_bytes(self::VERIFIER_BYTES)));
    }

    public function addressOf(AuthorizationRequest $request, string $clientId): string
    {
        return $this->endpoints->authorizationUrl.'?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->endpoints->redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $request->state,
            'code_challenge' => self::base64Url(hash('sha256', $request->codeVerifier, true)),
            'code_challenge_method' => 'S256',
        ], encoding_type: \PHP_QUERY_RFC3986);
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}

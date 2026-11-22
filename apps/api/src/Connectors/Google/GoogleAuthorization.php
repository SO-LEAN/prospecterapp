<?php

declare(strict_types=1);

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
    public const string GMAIL_SCOPE = 'https://www.googleapis.com/auth/gmail.readonly';
    /** the scope of Google of each scope Prospecter asks */
    public const array SCOPES_OF_GOOGLE = ['calendar' => self::CALENDAR_SCOPE, 'mailbox' => self::GMAIL_SCOPE];
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
        return $this->address($request, $clientId, ['scope' => self::SCOPES]);
    }

    /**
     * The reading of the mailbox asked with the calendar, by incremental consent: the token answered grants both, the
     * scopes granted before kept (ADR 0154); Google opens the account connected, rather than the one the browser
     * signed in to first.
     */
    public function addressImportingGmailOf(AuthorizationRequest $request, string $clientId, ?string $accountConnected): string
    {
        return $this->address($request, $clientId, [
            'scope' => self::SCOPES.' '.self::GMAIL_SCOPE,
            'include_granted_scopes' => 'true',
            ...(null === $accountConnected ? [] : ['login_hint' => $accountConnected]),
        ]);
    }

    /**
     * @param array<string, string> $asked
     */
    private function address(AuthorizationRequest $request, string $clientId, array $asked): string
    {
        return $this->endpoints->authorizationUrl.'?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->endpoints->redirectUri,
            'response_type' => 'code',
            ...$asked,
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

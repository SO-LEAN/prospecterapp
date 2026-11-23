<?php

declare(strict_types=1);

namespace App\Connectors\Google;

/**
 * Draws a new Google authorization: an unguessable `state` and a code verifier. Then it builds the address that asks
 * for Prospecter's calendar with a lasting access (ADR 0120). The address names the client of the member's account
 * (ADR 0125). It asks for `offline` access and for the consent each time, so that Google gives a refresh token. It
 * also carries the verifier's challenge (PKCE S256).
 */
final readonly class GoogleAuthorization
{
    public const string SCOPES = 'openid email '.self::CALENDAR_SCOPE;
    public const string CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar.app.created';
    public const string GMAIL_SCOPE = 'https://www.googleapis.com/auth/gmail.readonly';
    /** the Google scope for each scope that Prospecter asks for */
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
     * Asks for access to the mailbox along with the calendar, by incremental consent. The returned token grants both,
     * and keeps the scopes granted before (ADR 0154). Google opens the connected account, rather than the one the
     * browser signed in to first.
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

<?php

namespace App\Connectors\Google;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The addresses of Google (its double locally) and the page of the SPA Google brings the member back to, the same for
 * every account of the instance: each declares it in its own client (ADR 0120, 0125).
 */
final readonly class GoogleEndpoints
{
    public function __construct(
        #[Autowire(env: 'GOOGLE_REDIRECT_URI')]
        public string $redirectUri,
        #[Autowire(env: 'GOOGLE_AUTHORIZATION_URL')]
        public string $authorizationUrl,
        #[Autowire(env: 'GOOGLE_TOKEN_URL')]
        public string $tokenUrl,
        #[Autowire(env: 'GOOGLE_REVOKE_URL')]
        public string $revokeUrl,
        #[Autowire(env: 'GOOGLE_CALENDAR_URL')]
        public string $calendarUrl,
    ) {
    }
}

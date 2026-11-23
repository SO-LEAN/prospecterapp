<?php

declare(strict_types=1);

namespace App\Connectors\Google;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Google's addresses (its double, locally) and the SPA page that Google brings the member back to. The page is the
 * same for every account of the instance. Each account declares it in its own client (ADR 0120, 0125).
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
        #[Autowire(env: 'GOOGLE_GMAIL_URL')]
        public string $gmailUrl,
    ) {
    }
}

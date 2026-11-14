<?php

namespace App\Connectors\Google;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The OAuth client of the instance at Google (ADR 0120): its id and secret, values of the installation (SSM on AWS),
 * the addresses of Google (its double locally) and the page of the SPA Google brings the member back to.
 */
final readonly class GoogleClient
{
    public function __construct(
        #[Autowire(env: 'GOOGLE_CLIENT_ID')]
        public string $id,
        #[Autowire(env: 'GOOGLE_CLIENT_SECRET')]
        public string $secret,
        #[Autowire(env: 'GOOGLE_REDIRECT_URI')]
        public string $redirectUri,
        #[Autowire(env: 'GOOGLE_AUTHORIZATION_URL')]
        public string $authorizationUrl,
        #[Autowire(env: 'GOOGLE_TOKEN_URL')]
        public string $tokenUrl,
        #[Autowire(env: 'GOOGLE_REVOKE_URL')]
        public string $revokeUrl,
    ) {
    }
}

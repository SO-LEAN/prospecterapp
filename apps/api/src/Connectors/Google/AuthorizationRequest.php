<?php

declare(strict_types=1);

namespace App\Connectors\Google;

/**
 * An authorization that Prospecter is about to request from Google. It holds the `state` that Google will give back
 * and the code verifier (PKCE), which the core keeps. It also holds the Google address that the member's browser goes
 * to.
 */
final readonly class AuthorizationRequest
{
    public function __construct(
        public string $state,
        public string $codeVerifier,
    ) {
    }
}

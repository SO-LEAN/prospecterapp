<?php

declare(strict_types=1);

namespace App\Connectors\Google;

/**
 * An authorization about to be asked of Google: the `state` it will give back, the verifier of its code (PKCE), kept
 * by the core, and the address of Google the browser of the member goes to.
 */
final readonly class AuthorizationRequest
{
    public function __construct(
        public string $state,
        public string $codeVerifier,
    ) {
    }
}

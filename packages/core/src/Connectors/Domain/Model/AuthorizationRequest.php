<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * What a member asks a provider for. It holds the `state` that the provider gives back with its answer. It holds the
 * verifier of the code that the provider will give (PKCE). It also holds the scopes asked (ADR 0120, 0154).
 */
final readonly class AuthorizationRequest
{
    /**
     * @param list<Scope> $scopes
     */
    public function __construct(
        public Provider $provider,
        public string $state,
        public string $codeVerifier,
        public array $scopes = [],
    ) {
    }
}

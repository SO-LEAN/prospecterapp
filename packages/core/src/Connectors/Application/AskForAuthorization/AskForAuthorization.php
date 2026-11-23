<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\AskForAuthorization;

/**
 * I am about to ask a provider for access to my account there. The client draws the `state` that the provider gives
 * back with its answer, and the code verifier (PKCE). Both are kept for me while I answer the provider.
 */
final readonly class AskForAuthorization
{
    public function __construct(
        public string $memberId,
        public string $provider,
        public string $state,
        public string $codeVerifier,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

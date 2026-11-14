<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\AskForAuthorization;

/**
 * I am about to ask a provider for the access to my account there: the `state` it will give back with its answer,
 * and the verifier of its code (PKCE), both drawn by the client, are kept for me while I answer it.
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

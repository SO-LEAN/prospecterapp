<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application;

/**
 * A client an account made at a provider, as it is answered: its id, never its secret (ADR 0125).
 */
final readonly class AccountClient
{
    public function __construct(
        public string $provider,
        public string $clientId,
    ) {
    }
}

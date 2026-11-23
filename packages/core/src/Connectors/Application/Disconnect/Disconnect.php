<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Disconnect;

/**
 * I disconnect my account at a provider. The access that I gave is revoked there, and then forgotten.
 */
final readonly class Disconnect
{
    public function __construct(
        public string $memberId,
        public string $provider,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

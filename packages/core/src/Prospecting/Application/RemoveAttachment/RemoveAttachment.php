<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RemoveAttachment;

/**
 * Removes a file joined to an exchange, and forgets it.
 */
final readonly class RemoveAttachment
{
    public function __construct(
        public string $prospectorId,
        public string $exchangeId,
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

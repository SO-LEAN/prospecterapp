<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Attachment\RemoveAttachment;

/**
 * Removes a file attached to an exchange, and forgets it.
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

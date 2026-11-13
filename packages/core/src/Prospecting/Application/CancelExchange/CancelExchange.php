<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CancelExchange;

/**
 * Cancels a planned exchange, by its holder; who called it off, when said, by the values of `Canceller`: the
 * prospector or the contact.
 */
final readonly class CancelExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public ?string $canceller,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

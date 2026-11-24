<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\CancelExchange;

/**
 * Cancels a planned exchange. Only its holder cancels it. The holder may say who called it off, with the values of
 * `Canceller`: the prospector or the contact.
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

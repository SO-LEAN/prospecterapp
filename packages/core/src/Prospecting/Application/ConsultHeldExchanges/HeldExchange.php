<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultHeldExchanges;

/**
 * An exchange as its holder holds it, read without restoring it: its status and its channel by the values of
 * `ExchangeStatus` and `Channel`, the first organization it is kept for.
 */
final readonly class HeldExchange
{
    public function __construct(
        public string $id,
        public string $holderId,
        public string $status,
        public string $channel,
        public \DateTimeImmutable $startsAt,
        public ?int $durationInMinutes,
        public ?string $place,
        public ?string $link,
        public ?string $contactId,
        public string $organizationId,
    ) {
    }
}

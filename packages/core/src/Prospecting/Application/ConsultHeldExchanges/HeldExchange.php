<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultHeldExchanges;

/**
 * An exchange as its holder holds it, read without restoring it: its status and its channel by the values of
 * `ExchangeStatus` and `Channel`, the organizations it is kept for, none once it is left without one, about to be
 * erased.
 */
final readonly class HeldExchange
{
    /**
     * @param list<string> $organizationIds
     */
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
        public array $organizationIds,
    ) {
    }
}

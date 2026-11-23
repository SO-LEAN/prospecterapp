<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultHeldExchanges;

/**
 * An exchange as its holder holds it, read without restoring it. It has its status and its channel, with the values
 * of `ExchangeStatus` and `Channel`. It has the organizations it is kept for. It has none once it is left without an
 * organization and is about to be erased.
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
        public bool $linkRequested,
        public ?string $contactId,
        public array $organizationIds,
        public bool $bookedByLink = false,
    ) {
    }
}

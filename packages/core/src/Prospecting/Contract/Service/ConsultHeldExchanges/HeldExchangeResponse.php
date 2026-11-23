<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * The published language of an exchange as its holder holds it (ADR 0045, 0127). It gives:
 * - its holder and its status (`planned`, `done`, `missed`, `cancelled`);
 * - its channel (`call`, `meeting`, `videoCall`, and so on), its moment, and its duration when it was planned;
 * - a meeting's place or a video call's link;
 * - whether a video call without a link requests one from its holder's calendar (ADR 0137);
 * - its contact, or none when the exchange is with the organization itself;
 * - the first organization it is still kept for, with its name. An exchange with a contact to attach has none
 *   (ADR 0146);
 * - whether someone booked it through its holder's public link (ADR 0164).
 */
final readonly class HeldExchangeResponse
{
    public function __construct(
        public string $exchangeId,
        public string $holderId,
        public string $status,
        public string $channel,
        public \DateTimeImmutable $startsAt,
        public ?int $durationInMinutes,
        public ?string $place,
        public ?string $link,
        public bool $linkRequested,
        public ?string $contactId,
        public ?string $organizationId,
        public ?string $organizationName,
        public bool $bookedByLink = false,
    ) {
    }
}

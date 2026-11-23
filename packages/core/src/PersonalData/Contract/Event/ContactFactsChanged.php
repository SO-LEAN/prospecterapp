<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047, 0099). It carries a contact's state after a
 * change, as its facts count it. The highest revision is the latest. A forgotten contact is absent, at revision 0, with
 * no means to reach it. How the contact is reached is published from the event's second version, and is null in a
 * first-version fact. The event never carries the contact details, which are personal data.
 */
final readonly class ContactFactsChanged
{
    public function __construct(
        public string $accountId,
        public string $personId,
        public int $revision,
        public bool $present,
        public ?bool $hasEmail,
        public ?bool $hasPhone,
        public ?bool $hasMobile,
        public ?bool $hasLinkedIn,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}

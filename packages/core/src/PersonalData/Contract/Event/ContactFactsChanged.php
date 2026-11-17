<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047, 0099): the state of a contact once it
 * changed, as its facts count it. The highest revision is the latest; a forgotten contact is absent, at the
 * revision 0, reached by no means. How it is reached is published from its second version, null in a fact of the
 * first; never its contact details, personal data.
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

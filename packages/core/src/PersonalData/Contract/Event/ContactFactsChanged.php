<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047, 0099): the state of a contact once it
 * changed, as its facts count it. The highest revision is the latest; a forgotten contact is absent, at the
 * revision 0.
 */
final readonly class ContactFactsChanged
{
    public function __construct(
        public string $accountId,
        public string $personId,
        public int $revision,
        public bool $present,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}

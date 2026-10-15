<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047): a contact was erased by this member; no
 * organization may name it any more.
 */
final readonly class ContactForgotten
{
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $personId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * An integration event published to the other contexts (ADR 0046, 0047). Nobody attached this contact within 30 days
 * after their last meeting with the member who holds them. The contact must not stay (ADR 0146).
 */
final readonly class ContactLeftUnattached
{
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $personId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}

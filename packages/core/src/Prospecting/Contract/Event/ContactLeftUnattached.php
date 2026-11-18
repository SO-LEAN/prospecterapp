<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047): nobody attached this contact in the 30 days
 * after their last meeting with the member who holds them; they must not stay (ADR 0146).
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

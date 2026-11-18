<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseContactLeftUnattached;

/**
 * Erases a contact nobody attached in the 30 days after their last meeting (ADR 0146), asked by the schedule of
 * the platform, no member at its origin; nothing when they were attached, erased already, or not yet due.
 */
final readonly class EraseContactLeftUnattached
{
    public function __construct(
        public string $personId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

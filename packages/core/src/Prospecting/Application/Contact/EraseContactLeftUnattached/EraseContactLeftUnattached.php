<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\EraseContactLeftUnattached;

/**
 * Erases a contact that nobody attached in the 30 days after their last meeting (ADR 0146). The platform's schedule
 * asks for it, and no member is at its origin. It does nothing when the contact was attached, is already erased, or
 * is not due yet.
 */
final readonly class EraseContactLeftUnattached
{
    public function __construct(
        public string $personId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

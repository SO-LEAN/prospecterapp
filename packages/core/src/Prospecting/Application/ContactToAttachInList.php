<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * A contact waiting for their holder to attach them (ADR 0146): their last meeting, past or to come, and the moment
 * from which they are erased if nobody attaches them, the start of a day on the clock of that meeting.
 */
final readonly class ContactToAttachInList
{
    public function __construct(
        public string $personId,
        public \DateTimeImmutable $lastMeetingAt,
        public \DateTimeImmutable $erasableFrom,
    ) {
    }
}

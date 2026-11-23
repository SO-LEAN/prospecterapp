<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * A contact who waits for their holder to attach them (ADR 0146). It has their last meeting, past or to come. It
 * also has the moment from which they are erased if nobody attaches them: the start of a day, on that meeting's
 * clock.
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

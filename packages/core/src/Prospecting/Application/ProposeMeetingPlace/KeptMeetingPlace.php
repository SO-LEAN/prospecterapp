<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeMeetingPlace;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptMeetingPlace implements ProposeMeetingPlaceOutput
{
    public private(set) ?MeetingPlace $place = null;

    public function present(?MeetingPlace $place): void
    {
        $this->place = $place;
    }
}

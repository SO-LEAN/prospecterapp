<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultCalendar;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptCalendar implements ConsultCalendarOutput
{
    public private(set) Calendar $calendar;

    public function present(Calendar $calendar): void
    {
        $this->calendar = $calendar;
    }
}

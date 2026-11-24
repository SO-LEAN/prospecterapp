<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultCalendar;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptCalendar implements ConsultCalendarOutput
{
    public private(set) Calendar $calendar;

    public function present(Calendar $calendar): void
    {
        $this->calendar = $calendar;
    }
}

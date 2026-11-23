<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application;

/**
 * A host's booking page as they set it. It stays closed and empty until the host's first action on it.
 */
final readonly class MyBookingPage
{
    /**
     * @param list<array{weekday: string, from: string, to: string}>     $windows           Monday first, times HH:MM
     * @param ?string                                                    $timeZone          the clock of the windows, null until they are set
     * @param list<array{id: string, firstDay: string, lastDay: string}> $inactivePeriods   the periods that had not ended before the reading's day, the soonest first
     * @param list<string>                                               $channels          `call`, `videoCall`
     * @param ?string                                                    $code              the public link's code, null until the first opening
     * @param bool                                                       $calendarConnected whether the calendar that invites the bookers is connected
     * @param list<string>                                               $possibleIntents   the names of the intents the page's state allows (`OpenBooking`, …)
     * @param bool                                                       $suspended         whether the page is open but offers nothing, because its calendar is not connected
     */
    public function __construct(
        public bool $open,
        public array $windows,
        public ?string $timeZone,
        public array $inactivePeriods,
        public array $channels,
        public ?string $code,
        public bool $calendarConnected,
        public array $possibleIntents,
        public bool $suspended,
    ) {
    }
}

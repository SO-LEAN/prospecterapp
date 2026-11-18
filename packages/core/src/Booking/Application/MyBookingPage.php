<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application;

/**
 * The booking page of a host as they set it: closed and empty until their first gesture on it.
 */
final readonly class MyBookingPage
{
    /**
     * @param list<array{weekday: string, from: string, to: string}>     $windows           Monday first, times HH:MM
     * @param ?string                                                    $timeZone          the clock of the windows, none before they are set
     * @param list<array{id: string, firstDay: string, lastDay: string}> $inactivePeriods   those not ended before the day of the reading, the soonest first
     * @param list<string>                                               $channels          `call`, `videoCall`
     * @param ?string                                                    $code              the code of the public link, none before the first opening
     * @param bool                                                       $calendarConnected whether the calendar that invites the bookers is connected
     * @param list<string>                                               $possibleIntents   the names of the intents its state offers (`OpenBooking`, …)
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
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultPublicBookingPage;

/**
 * A booking page as its public link needs it: its host, whether it is open, how and when the host may be booked.
 */
final readonly class BookingSchedule
{
    /**
     * @param list<string>                                               $channels        `call`, `videoCall`
     * @param list<array{weekday: string, from: string, to: string}>     $windows         Monday first, times HH:MM
     * @param ?string                                                    $timeZone        the clock of the windows, none before they are set
     * @param list<array{id: string, firstDay: string, lastDay: string}> $inactivePeriods
     */
    public function __construct(
        public string $host,
        public string $accountId,
        public bool $open,
        public array $channels,
        public array $windows,
        public ?string $timeZone,
        public array $inactivePeriods,
    ) {
    }
}

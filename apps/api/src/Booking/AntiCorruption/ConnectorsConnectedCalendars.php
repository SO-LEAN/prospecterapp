<?php

namespace App\Booking\AntiCorruption;

use Prospecter\Core\Booking\Domain\Port\ConnectedCalendars;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\KeptConnectedCalendars;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendar;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendarService;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer (ADR 0045, 0089): Booking knows whether the calendar of a host is connected from Connectors,
 * asked about a set of one.
 */
final readonly class ConnectorsConnectedCalendars implements ConnectedCalendars
{
    public function __construct(private WhichHaveAConnectedCalendarService $whichHaveAConnectedCalendarService)
    {
    }

    public function isConnectedFor(MemberId $host): bool
    {
        $kept = new KeptConnectedCalendars();
        ($this->whichHaveAConnectedCalendarService)(new WhichHaveAConnectedCalendar([$host->value]), $kept);

        return in_array($host->value, $kept->memberIds, true);
    }
}

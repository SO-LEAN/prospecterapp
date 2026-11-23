<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Domain\Port\ConnectedCalendars;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\KeptConnectedCalendars;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendar;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendarService;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089). Booking learns from Connectors whether a host's
 * calendar is connected, by asking about a set of one.
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

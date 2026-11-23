<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: whether a host's calendar is connected. That calendar invites the bookers. The adapter translates the answer of
 * Connectors, the context that connects calendars, through its published service `WhichHaveAConnectedCalendarService`.
 */
interface ConnectedCalendars
{
    public function isConnectedFor(MemberId $host): bool;
}

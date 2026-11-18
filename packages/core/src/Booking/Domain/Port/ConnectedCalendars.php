<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: whether the calendar of a host is connected, the one that will invite the bookers, translated from the
 * context that connects it (Connectors), through its published service `WhichHaveAConnectedCalendarService`.
 */
interface ConnectedCalendars
{
    public function isConnectedFor(MemberId $host): bool;
}

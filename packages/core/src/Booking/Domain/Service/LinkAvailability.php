<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Service;

use Prospecter\Core\Booking\Domain\Port\ConnectedCalendars;
use Prospecter\Core\Booking\Domain\Port\Hosts;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Whether the public link of an open page offers slots (ADR 0153, 0164): while the calendar of its host is connected,
 * which invites the bookers, and the host may hold the contacts their bookers become (ADR 0146).
 */
final readonly class LinkAvailability
{
    public function __construct(
        private ConnectedCalendars $calendars,
        private Hosts $hosts,
    ) {
    }

    public function offersSlots(bool $open, MemberId $host): bool
    {
        return $open && $this->calendars->isConnectedFor($host) && $this->hosts->mayHoldBookers($host);
    }
}

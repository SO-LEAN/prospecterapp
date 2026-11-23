<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Service;

use Prospecter\Core\Booking\Domain\Port\ConnectedCalendars;
use Prospecter\Core\Booking\Domain\Port\Hosts;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Whether an open page's public link offers slots (ADR 0153, 0164). It does while the host's calendar, which invites
 * the bookers, is connected, and while the host can hold the contacts that their bookers become (ADR 0146).
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

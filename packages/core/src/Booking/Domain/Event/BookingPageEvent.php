<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Something that happened to the booking page of a host, by that host: the page is theirs alone.
 */
abstract readonly class BookingPageEvent extends DomainEvent
{
    public function subject(): MemberId
    {
        return $this->by;
    }
}

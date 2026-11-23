<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * An event on a host's booking page. Only the host acts on it, because the page is theirs alone.
 */
abstract readonly class BookingPageEvent extends DomainEvent
{
    public function subject(): MemberId
    {
        return $this->by;
    }
}

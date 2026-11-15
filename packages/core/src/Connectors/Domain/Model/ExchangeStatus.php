<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * Where an exchange stands, as Prospecting tells it: planned, it is in the calendar of its holder; done or missed,
 * its event stays as it was; cancelled, it leaves the calendar.
 */
enum ExchangeStatus: string
{
    case Planned = 'planned';
    case Done = 'done';
    case Missed = 'missed';
    case Cancelled = 'cancelled';
}

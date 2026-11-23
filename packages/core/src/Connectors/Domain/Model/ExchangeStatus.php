<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * An exchange's status, as Prospecting tells it. A planned exchange is in its holder's calendar. A done or missed
 * exchange keeps its event as it was. A cancelled exchange leaves the calendar.
 */
enum ExchangeStatus: string
{
    case Planned = 'planned';
    case Done = 'done';
    case Missed = 'missed';
    case Cancelled = 'cancelled';
}

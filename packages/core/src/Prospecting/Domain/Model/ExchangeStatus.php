<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * Where an exchange stands: planned, still to come, or done; an exchange noted once it happened is done.
 */
enum ExchangeStatus: string
{
    case Planned = 'planned';
    case Done = 'done';
}

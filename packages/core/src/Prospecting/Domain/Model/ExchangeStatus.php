<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * Where an exchange stands: planned, still to come; done, or missed when the contact did not come or answer; or
 * cancelled before it happened. An exchange noted once it happened is done or missed.
 */
enum ExchangeStatus: string
{
    case Planned = 'planned';
    case Done = 'done';
    case Missed = 'missed';
    case Cancelled = 'cancelled';

    /**
     * What became of an exchange noted, or corrected, once it happened.
     */
    public static function outcome(bool $missed): self
    {
        return $missed ? self::Missed : self::Done;
    }
}

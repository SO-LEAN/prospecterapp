<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * Where an exchange stands. A planned exchange is still to come. A done exchange took place. A missed exchange did
 * not, because the contact did not come or answer. A cancelled exchange was called off before it happened. An
 * exchange logged after it happened is done or missed.
 */
enum ExchangeStatus: string
{
    case Planned = 'planned';
    case Done = 'done';
    case Missed = 'missed';
    case Cancelled = 'cancelled';

    /**
     * What became of an exchange logged or corrected after it happened.
     */
    public static function outcome(bool $missed): self
    {
        return $missed ? self::Missed : self::Done;
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Slot;

/**
 * What a prospector let pass at the moment of a reading, on their clock. A planned exchange is late once its slot has
 * ended and nobody marked it done, missed or cancelled. A to-do is late when it is not done and its hour has passed, or
 * its day when it has no hour.
 */
final class Lateness
{
    #[\NoDiscard]
    public static function ofExchange(ExchangeStatus $status, Slot $slot, \DateTimeImmutable $at): bool
    {
        return ExchangeStatus::Planned === $status && $slot->hasEndedBy($at);
    }

    #[\NoDiscard]
    public static function ofToDo(Due $due, bool $done, \DateTimeImmutable $at): bool
    {
        return !$done && $due->hasPassedBy($at);
    }
}

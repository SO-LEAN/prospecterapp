<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Slot;

/**
 * What a prospector let pass at the moment of a reading, on their clock: a planned exchange whose slot has ended,
 * still to mark done, missed or cancelled; a to-do not done whose hour, or else whose day, has passed.
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

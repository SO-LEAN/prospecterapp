<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Model;

use Prospecter\Core\Accounts\Domain\Exception\ReminderDelayNotOffered;

/**
 * How long before a planned exchange a member is reminded of it, among the delays offered.
 */
enum ReminderDelay: int
{
    case OnTime = 0;
    case FiveMinutes = 5;
    case TenMinutes = 10;
    case FifteenMinutes = 15;
    case HalfAnHour = 30;
    case AnHour = 60;

    /**
     * @throws ReminderDelayNotOffered
     */
    public static function ofMinutes(int $minutes): self
    {
        return self::tryFrom($minutes) ?? throw ReminderDelayNotOffered::of($minutes);
    }
}

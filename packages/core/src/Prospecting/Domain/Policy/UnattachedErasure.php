<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

/**
 * Prospecter erases a contact to attach that nobody attaches. The erasure comes from the start of the 30th day after
 * their last meeting. That day is read on the clock the meeting was given in (ADR 0146).
 */
final class UnattachedErasure
{
    public const int DAYS_KEPT = 30;

    #[\NoDiscard]
    public static function from(\DateTimeImmutable $lastMeeting, \DateTimeZone $clock): \DateTimeImmutable
    {
        return $lastMeeting->setTimezone($clock)->setTime(0, 0)->modify(sprintf('+%d days', self::DAYS_KEPT));
    }
}

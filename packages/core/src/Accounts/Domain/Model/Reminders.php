<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Model;

/**
 * Whether a member is reminded of the exchanges they planned, and how long before; turned off, the delay is kept for
 * when they turn them on again.
 */
final readonly class Reminders
{
    public function __construct(
        public bool $enabled,
        public ReminderDelay $delay,
    ) {
    }

    public static function byDefault(): self
    {
        return new self(true, ReminderDelay::TenMinutes);
    }
}

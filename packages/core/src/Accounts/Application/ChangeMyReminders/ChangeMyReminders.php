<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyReminders;

/**
 * Turns my reminders of the exchanges I planned on or off, and sets how long before they come.
 */
final readonly class ChangeMyReminders
{
    public function __construct(
        public string $memberId,
        public bool $enabled,
        public int $minutesBefore,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

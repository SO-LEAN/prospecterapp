<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyReminders;

use Prospecter\Core\Accounts\Application\MyReminders;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptReminders implements ConsultMyRemindersOutput
{
    public private(set) MyReminders $reminders;

    public function present(MyReminders $reminders): void
    {
        $this->reminders = $reminders;
    }
}

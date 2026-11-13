<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyReminders;

use Prospecter\Core\Accounts\Application\MyReminders;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultMyRemindersOutput extends OutputPort
{
    public function present(MyReminders $reminders): void;
}

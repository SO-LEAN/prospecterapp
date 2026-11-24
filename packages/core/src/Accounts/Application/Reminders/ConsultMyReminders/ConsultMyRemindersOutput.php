<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Reminders\ConsultMyReminders;

use Prospecter\Core\Accounts\Application\Reminders\MyReminders;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultMyRemindersOutput extends OutputPort
{
    public function present(MyReminders $reminders): void;
}

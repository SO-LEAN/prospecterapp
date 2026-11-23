<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Port;

use Prospecter\Core\Accounts\Application\MyReminders;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads a member's reminders without restoring the member (ADR 0088). A member who never changed their reminders
 * gets the default ones.
 */
interface MyRemindersRetriever
{
    /**
     * @throws UnknownMember
     */
    public function of(MemberId $me): MyReminders;
}

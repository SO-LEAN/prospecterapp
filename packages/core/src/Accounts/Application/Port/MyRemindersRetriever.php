<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Port;

use Prospecter\Core\Accounts\Application\MyReminders;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the reminders of a member without restoring them (ADR 0088), those by default when they never changed them.
 */
interface MyRemindersRetriever
{
    /**
     * @throws UnknownMember
     */
    public function of(MemberId $me): MyReminders;
}

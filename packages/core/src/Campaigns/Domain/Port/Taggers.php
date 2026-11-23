<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the members who tag, and the account they tag for. It translates what Accounts publishes through its service
 * `ConsultMembersService` (ADR 0045, 0089).
 */
interface Taggers
{
    /**
     * @throws UnknownMember
     */
    public function accountOf(MemberId $member): AccountId;
}

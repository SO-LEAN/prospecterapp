<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Port: the members who run campaigns, and the account they run them for. It translates what Accounts publishes through
 * its service `ConsultMembersService` (ADR 0045, 0089). The API decides who may run them (ADR 0081).
 */
interface Campaigners
{
    /**
     * @throws UnknownMember
     */
    public function accountOf(MemberId $member): AccountId;
}

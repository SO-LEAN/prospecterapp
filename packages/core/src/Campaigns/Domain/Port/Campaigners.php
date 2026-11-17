<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the members who run campaigns, and the account they run them for, translated from the context that knows
 * them (Accounts), through its published service `ConsultMembersService` (ADR 0045, 0089). Who may run them is the
 * business of the API (ADR 0081).
 */
interface Campaigners
{
    /**
     * @throws UnknownMember
     */
    public function accountOf(MemberId $member): AccountId;
}

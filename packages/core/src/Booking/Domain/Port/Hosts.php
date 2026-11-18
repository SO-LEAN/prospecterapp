<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Port;

use Prospecter\Core\Booking\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the members who host a booking page, and their account, translated from the context that knows them
 * (Accounts), through its published service `ConsultMembersService` (ADR 0045, 0089).
 */
interface Hosts
{
    /**
     * @throws UnknownMember
     */
    public function accountOf(MemberId $host): AccountId;
}

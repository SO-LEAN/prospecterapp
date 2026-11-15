<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\UnknownMember;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the members who connect their accounts, and who administer the clients of their account, translated from the
 * context that knows them (Accounts), through its published service `ConsultMembersService` (ADR 0045, 0089).
 */
interface Members
{
    /**
     * @throws UnknownMember
     */
    public function get(MemberId $member): Member;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\UnknownMember;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the members who connect their accounts and who administer their account's clients (ADR 0045, 0089). It is
 * translated from Accounts, the context that knows them, through its published service `ConsultMembersService`.
 */
interface Members
{
    /**
     * @throws UnknownMember
     */
    public function get(MemberId $member): Member;
}

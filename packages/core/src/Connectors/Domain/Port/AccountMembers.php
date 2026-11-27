<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\UnknownMember;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Port: the members who connect their accounts and who administer their account's clients (ADR 0045, 0089, 0223).
 * It is translated from Accounts, the context that knows them, through its published service `ConsultMembersService`.
 * The member it gives has no first name yet.
 */
interface AccountMembers
{
    /**
     * @throws UnknownMember
     */
    public function get(MemberId $member): Member;
}

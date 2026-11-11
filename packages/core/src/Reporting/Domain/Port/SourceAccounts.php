<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Port;

use Prospecter\Core\Reporting\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the accounts whose figures Reporting keeps, translated from the context that knows them (Accounts), through
 * its published services `ListAccountsService` and `ConsultMembersService` (ADR 0045, 0089).
 */
interface SourceAccounts
{
    /**
     * @return iterable<AccountId>
     */
    public function all(): iterable;

    /**
     * @throws UnknownMember
     */
    public function accountOf(MemberId $member): AccountId;
}

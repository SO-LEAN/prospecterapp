<?php

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Application\MyReminders;
use Prospecter\Core\Accounts\Application\Port\MyRemindersRetriever;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the reminders in the item of the member (`MEMBER#<id>` / `MEMBER`) that `DynamoMemberRepository` writes,
 * without restoring them (ADR 0088).
 */
final readonly class DynamoMyRemindersRetriever implements MyRemindersRetriever
{
    public function __construct(private Table $accountsTable)
    {
    }

    public function of(MemberId $me): MyReminders
    {
        $member = ($this->accountsTable->item(AccountsKeys::member($me->value), AccountsKeys::MEMBER) ?? throw UnknownMember::identifiedBy($me))['member'];
        $reminders = RemindersDocument::restore($member['reminders'] ?? null);

        return new MyReminders($reminders->enabled, $reminders->delay->value);
    }
}

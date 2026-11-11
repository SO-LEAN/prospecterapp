<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\KeptAccounts;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\ListAccounts;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\ListAccountsService;
use Prospecter\Core\Reporting\Domain\Exception\UnknownMember;
use Prospecter\Core\Reporting\Domain\Port\SourceAccounts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089): Reporting counts accounts, Accounts publishes
 * them, and the members of each, slice after slice.
 */
final readonly class AccountsSourceAccounts implements SourceAccounts
{
    private const int SLICE = 100;

    public function __construct(private ListAccountsService $listAccountsService, private ConsultMembersService $consultMembersService)
    {
    }

    public function all(): iterable
    {
        $after = null;

        do {
            $kept = new KeptAccounts();
            ($this->listAccountsService)(new ListAccounts($after, self::SLICE), $kept);

            foreach ($kept->accountIds as $accountId) {
                yield new AccountId($accountId);
            }

            $after = $kept->next;
        } while (null !== $after);
    }

    public function accountOf(MemberId $member): AccountId
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$member->value]), $kept);

        return new AccountId(($kept->members[$member->value] ?? throw UnknownMember::identifiedBy($member))->accountId);
    }
}

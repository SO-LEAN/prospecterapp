<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Connectors\Domain\Exception\UnknownMember;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089): Connectors knows the account of a member from
 * Accounts, asked about a set of one.
 */
final readonly class AccountsMembers implements Members
{
    public function __construct(private ConsultMembersService $consultMembersService)
    {
    }

    public function accountOf(MemberId $member): AccountId
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$member->value]), $kept);

        return new AccountId(($kept->members[$member->value] ?? throw UnknownMember::identifiedBy($member))->accountId);
    }
}

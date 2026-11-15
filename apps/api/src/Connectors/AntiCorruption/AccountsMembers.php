<?php

namespace App\Connectors\AntiCorruption;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Connectors\Domain\Exception\UnknownMember;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer (ADR 0045, 0089): Connectors knows the account of a member from
 * Accounts, asked about a set of one.
 */
final readonly class AccountsMembers implements Members
{
    public function __construct(private ConsultMembersService $consultMembersService)
    {
    }

    public function get(MemberId $member): Member
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$member->value]), $kept);
        $consulted = $kept->members[$member->value] ?? throw UnknownMember::identifiedBy($member);

        return new Member($member, new AccountId($consulted->accountId), $consulted->administers);
    }
}

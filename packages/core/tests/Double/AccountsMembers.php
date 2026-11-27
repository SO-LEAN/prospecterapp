<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Connectors\Domain\Exception\UnknownMember;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Port\AccountMembers;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089, 0223). From Accounts, Connectors learns a member's account, whether they
 * administer it, how long before their exchanges they are reminded, and the person they are. It asks about a set of
 * one.
 */
final readonly class AccountsMembers implements AccountMembers
{
    public function __construct(private ConsultMembersService $consultMembersService)
    {
    }

    public function get(MemberId $member): Member
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$member->value]), $kept);
        $consulted = $kept->members[$member->value] ?? throw UnknownMember::identifiedBy($member);

        return new Member($member, new AccountId($consulted->accountId), $consulted->administers, $consulted->reminderMinutesBefore, $consulted->readsPersonalData, $consulted->personId);
    }
}

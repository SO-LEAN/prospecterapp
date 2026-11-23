<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Connectors\Domain\Exception\UnknownMember;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\KeptMemberPresentations;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembers;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembersService;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089). From Accounts, Connectors learns a member's
 * account, whether they administer it, and how long before their exchanges they are reminded. From PersonalData, it
 * learns their first name as they present themselves. Each one is asked about a set of one.
 */
final readonly class AccountsAndPersonalDataMembers implements Members
{
    public function __construct(
        private ConsultMembersService $consultMembersService,
        private PresentMembersService $presentMembersService,
    ) {
    }

    public function get(MemberId $member): Member
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$member->value]), $kept);
        $consulted = $kept->members[$member->value] ?? throw UnknownMember::identifiedBy($member);

        return new Member($member, new AccountId($consulted->accountId), $consulted->administers, $consulted->reminderMinutesBefore, $consulted->readsPersonalData, $this->firstNameOf($consulted->personId));
    }

    private function firstNameOf(string $person): ?string
    {
        $kept = new KeptMemberPresentations();
        ($this->presentMembersService)(new PresentMembers([$person]), $kept);

        return $kept->members[0]->firstName ?? null;
    }
}

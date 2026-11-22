<?php

declare(strict_types=1);

namespace App\Booking\AntiCorruption;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\Booking\Domain\Exception\UnknownMember;
use Prospecter\Core\Booking\Domain\Model\HostIdentity;
use Prospecter\Core\Booking\Domain\Port\Hosts;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\KeptMemberPresentations;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembers;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembersService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0153): Booking knows the account of a host and the name of its organization
 * from Accounts, how the host presents themselves from PersonalData, each asked about a set of one.
 */
final readonly class AccountsAndPersonalDataHosts implements Hosts
{
    public function __construct(
        private ConsultMembersService $consultMembersService,
        private PresentMembersService $presentMembersService,
    ) {
    }

    public function accountOf(MemberId $host): AccountId
    {
        return new AccountId($this->memberOf($host)->accountId);
    }

    public function identityOf(MemberId $host): HostIdentity
    {
        $member = $this->memberOf($host);
        $kept = new KeptMemberPresentations();
        ($this->presentMembersService)(new PresentMembers([$member->personId]), $kept);
        $presented = $kept->members[0] ?? null;

        return new HostIdentity($presented?->firstName, $presented?->lastName, $presented?->pictureUrl, $member->accountName);
    }

    /**
     * Whether the host may hold the contacts their bookers become: a member with the right to manage personal data
     * (ADR 0146, 0164). An unknown member may not.
     */
    public function mayHoldBookers(MemberId $host): bool
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$host->value]), $kept);

        return $kept->members[$host->value]->readsPersonalData ?? false;
    }

    private function memberOf(MemberId $host): MemberResponse
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$host->value]), $kept);

        return $kept->members[$host->value] ?? throw UnknownMember::identifiedBy($host);
    }
}

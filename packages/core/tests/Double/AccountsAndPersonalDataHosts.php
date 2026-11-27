<?php

declare(strict_types=1);

namespace Tests\Core\Double;

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
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089, 0153). Booking learns a host's account and its
 * organization's name from Accounts. It learns how the host presents themselves from PersonalData. It asks each one
 * about a set of one.
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

    public function readsPersonalData(MemberId $host): bool
    {
        return $this->memberOf($host)->readsPersonalData;
    }

    private function memberOf(MemberId $host): MemberResponse
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$host->value]), $kept);

        return $kept->members[$host->value] ?? throw UnknownMember::identifiedBy($host);
    }
}

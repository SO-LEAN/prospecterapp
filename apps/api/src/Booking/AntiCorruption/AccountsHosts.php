<?php

declare(strict_types=1);

namespace App\Booking\AntiCorruption;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\Booking\Domain\Exception\UnknownMember;
use Prospecter\Core\Booking\Domain\Model\HostMembership;
use Prospecter\Core\Booking\Domain\Port\Hosts;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0153, 0223). Booking learns from Accounts a host's account, its organization's
 * name, the person the host is and their right on personal data. It asks about a set of one.
 */
final readonly class AccountsHosts implements Hosts
{
    public function __construct(private ConsultMembersService $consultMembersService)
    {
    }

    public function accountOf(MemberId $host): AccountId
    {
        return new AccountId($this->memberOf($host)->accountId);
    }

    public function membershipOf(MemberId $host): HostMembership
    {
        $member = $this->memberOf($host);

        return new HostMembership($member->personId, $member->accountName);
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

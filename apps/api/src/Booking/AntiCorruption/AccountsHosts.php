<?php

namespace App\Booking\AntiCorruption;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Booking\Domain\Exception\UnknownMember;
use Prospecter\Core\Booking\Domain\Port\Hosts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer (ADR 0045, 0089): Booking knows the account of a host from Accounts, asked about a set of one.
 */
final readonly class AccountsHosts implements Hosts
{
    public function __construct(private ConsultMembersService $consultMembersService)
    {
    }

    public function accountOf(MemberId $host): AccountId
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$host->value]), $kept);

        return new AccountId(($kept->members[$host->value] ?? throw UnknownMember::identifiedBy($host))->accountId);
    }
}

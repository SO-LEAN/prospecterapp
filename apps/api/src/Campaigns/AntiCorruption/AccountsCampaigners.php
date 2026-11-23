<?php

declare(strict_types=1);

namespace App\Campaigns\AntiCorruption;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownMember;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0144). Campaigns learns from Accounts the account of a member who runs
 * campaigns. It asks about a set of one.
 */
final readonly class AccountsCampaigners implements Campaigners
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

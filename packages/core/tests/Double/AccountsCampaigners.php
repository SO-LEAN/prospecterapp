<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownMember;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The API's anticorruption layer, with the same code. Campaigns learns from Accounts the account of a member who runs
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

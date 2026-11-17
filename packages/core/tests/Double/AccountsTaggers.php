<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownMember;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089): Campaigns knows the account of a member who
 * tags from Accounts, asked about a set of one.
 */
final readonly class AccountsTaggers implements Taggers
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

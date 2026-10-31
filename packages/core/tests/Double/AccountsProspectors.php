<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownProspector;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089): Prospecting knows prospectors, Accounts publishes its members.
 */
final readonly class AccountsProspectors implements Prospectors
{
    public function __construct(private ConsultMembersService $consultMembersService)
    {
    }

    public function prospectorFrom(MemberId $id): Prospector
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$id->value]), $kept);
        $member = $kept->members[$id->value] ?? throw UnknownProspector::identifiedBy($id);

        return new Prospector($id, new AccountId($member->accountId), $member->readsPersonalData);
    }
}

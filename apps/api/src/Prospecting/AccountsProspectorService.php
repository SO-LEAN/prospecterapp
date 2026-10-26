<?php

namespace App\Prospecting;

use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\KeptMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\MemberResponse;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownProspector;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer (ADR 0045): Prospecting knows prospectors, Accounts publishes its members.
 */
final readonly class AccountsProspectorService implements ProspectorService
{
    public function __construct(private RefusalTranslatingHandler $consultMember)
    {
    }

    public function prospectorFrom(MemberId $id): Prospector
    {
        $member = $this->memberOf($id);

        return new Prospector($id, new AccountId($member->accountId), $member->readsPersonalData);
    }

    private function memberOf(MemberId $id): MemberResponse
    {
        $kept = new KeptMember();

        try {
            ($this->consultMember)(new ConsultMember((string) $id), $kept);
        } catch (UseCaseRefused) {
            throw UnknownProspector::identifiedBy($id);
        }

        return $kept->member;
    }
}

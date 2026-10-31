<?php

namespace App\PersonalData\AntiCorruption;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownReader;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer (ADR 0045, 0089): PersonalData knows readers, Accounts publishes its members.
 */
final readonly class AccountsReaders implements Readers
{
    public function __construct(private ConsultMembersService $consultMembersService)
    {
    }

    public function readerFrom(MemberId $id): Reader
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers([$id->value]), $kept);
        $member = $kept->members[$id->value] ?? throw UnknownReader::identifiedBy($id);

        return new Reader($id, new AccountId($member->accountId), new PersonId($member->personId), $member->readsPersonalData);
    }
}

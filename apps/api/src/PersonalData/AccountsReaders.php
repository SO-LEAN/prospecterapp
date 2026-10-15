<?php

namespace App\PersonalData;

use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownReader;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer: PersonalData knows readers, Accounts knows members (ADR 0031).
 */
final readonly class AccountsReaders implements Readers
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function get(MemberId $id): Reader
    {
        try {
            $member = $this->members->get($id);
        } catch (UnknownMember) {
            throw UnknownReader::identifiedBy($id);
        }

        return new Reader($id, $member->accountId(), $member->personId(), $member->readsPersonalData());
    }
}

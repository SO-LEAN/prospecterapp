<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownReader;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The readers read from the members of the accounts, like the anticorruption layer of the API.
 */
final readonly class MembersAsReaders implements Readers
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

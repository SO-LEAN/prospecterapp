<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Service;

use Prospecter\Core\Connectors\Domain\Exception\UnknownMember;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Port\AccountMembers;
use Prospecter\Core\Connectors\Domain\Port\MemberFirstNames;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The members who connect their accounts: what Accounts knows of them, and the first name they present themselves
 * with in PersonalData (ADR 0175, 0223).
 */
final readonly class Members
{
    public function __construct(
        private AccountMembers $accountMembers,
        private MemberFirstNames $firstNames,
    ) {
    }

    /**
     * @throws UnknownMember
     */
    public function get(MemberId $member): Member
    {
        $known = $this->accountMembers->get($member);

        return $known->presentedAs($this->firstNames->firstNameOf($known->personId));
    }
}

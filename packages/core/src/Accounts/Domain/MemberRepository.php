<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\SharedKernel\Domain\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\MemberId;

interface MemberRepository
{
    public function nextIdentity(): MemberId;

    /**
     * @throws UnknownMember
     */
    public function get(MemberId $id): Member;

    /**
     * @throws EmailAlreadyUsed
     */
    public function save(Member $member): void;
}

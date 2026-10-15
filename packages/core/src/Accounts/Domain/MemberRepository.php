<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\MemberId;

interface MemberRepository
{
    /**
     * @throws UnknownMember
     */
    public function get(MemberId $id): Member;

    /**
     * @throws EmailAlreadyUsed
     */
    public function save(Member $member): void;
}

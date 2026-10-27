<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMember;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptMember implements ConsultMemberOutput
{
    public private(set) MemberResponse $member;

    public function present(MemberResponse $member): void
    {
        $this->member = $member;
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMembers;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptMembers implements ConsultMembersOutput
{
    /**
     * @var array<string, MemberResponse>
     */
    public private(set) array $members = [];

    /**
     * @param array<string, MemberResponse> $members
     */
    public function present(array $members): void
    {
        $this->members = $members;
    }
}

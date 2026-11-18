<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\PresentMembers;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptMemberPresentations implements PresentMembersOutput
{
    /**
     * @var list<MemberPresentationResponse>
     */
    public private(set) array $members = [];

    public function present(array $members): void
    {
        $this->members = $members;
    }
}

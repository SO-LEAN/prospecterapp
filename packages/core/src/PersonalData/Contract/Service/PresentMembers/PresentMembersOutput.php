<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\PresentMembers;

interface PresentMembersOutput
{
    /**
     * @param list<MemberPresentationResponse> $members the persons asked who are members of their account
     */
    public function present(array $members): void;
}

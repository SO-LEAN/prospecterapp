<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMembers;

interface ConsultMembersOutput
{
    /**
     * @param array<string, MemberResponse> $members the members found, by id; an unknown one is absent
     */
    public function present(array $members): void;
}

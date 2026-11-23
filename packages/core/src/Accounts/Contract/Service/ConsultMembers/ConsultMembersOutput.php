<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMembers;

interface ConsultMembersOutput
{
    /**
     * @param array<string, MemberResponse> $members the members found, by id. An unknown member is absent.
     */
    public function present(array $members): void;
}

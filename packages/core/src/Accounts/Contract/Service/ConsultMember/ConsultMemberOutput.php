<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMember;

interface ConsultMemberOutput
{
    public function present(MemberResponse $member): void;
}

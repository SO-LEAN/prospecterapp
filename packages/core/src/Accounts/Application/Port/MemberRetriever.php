<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Port;

use Prospecter\Core\Accounts\Contract\Service\ConsultMember\MemberResponse;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads a member as the other contexts know them (ADR 0045, 0047) without restoring them (ADR 0088), with their
 * right to read the personal data (`PersonalDataRight`).
 */
interface MemberRetriever
{
    /**
     * @throws UnknownMember
     */
    public function get(MemberId $id): MemberResponse;
}

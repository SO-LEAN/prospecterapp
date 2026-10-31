<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Port;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads members as the other contexts know them (ADR 0045, 0047, 0089) without restoring them (ADR 0088), with
 * their right to read the personal data (`PersonalDataRight`).
 */
interface MemberRetriever
{
    /**
     * @param list<MemberId> $ids
     *
     * @return array<string, MemberResponse> the members found among these ids, by id
     */
    public function ofIds(array $ids): array;
}

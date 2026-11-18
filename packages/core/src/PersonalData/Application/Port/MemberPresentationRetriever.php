<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Application\PresentMembers\MemberPresentation;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads how persons would present themselves without restoring them (ADR 0088).
 */
interface MemberPresentationRetriever
{
    /**
     * The persons that exist among these ids, in no particular order.
     *
     * @param list<PersonId> $ids
     *
     * @return list<MemberPresentation>
     */
    public function ofIds(array $ids): array;
}

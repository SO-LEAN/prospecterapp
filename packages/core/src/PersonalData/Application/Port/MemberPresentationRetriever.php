<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Application\Profile\MemberPresentation;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;

/**
 * Reads how persons would present themselves, without restoring them (ADR 0088).
 */
interface MemberPresentationRetriever
{
    /**
     * Returns the persons who exist among these ids, in no particular order.
     *
     * @param list<PersonId> $ids
     *
     * @return list<MemberPresentation>
     */
    public function ofIds(array $ids): array;
}

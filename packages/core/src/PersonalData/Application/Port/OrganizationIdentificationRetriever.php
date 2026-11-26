<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Application\Identification\Identification;
use Prospecter\Core\PersonalData\Application\Identification\OrganizationClues;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Asks Prospecting which of the account's organizations some clues designate (ADR 0173, 0217), so that a person is
 * searched first among the contacts of the organization they work for.
 */
interface OrganizationIdentificationRetriever
{
    /**
     * @param list<OrganizationClues> $organizations
     *
     * @return list<Identification> one per organization, in the order given
     */
    public function identify(MemberId $reader, array $organizations): array;
}

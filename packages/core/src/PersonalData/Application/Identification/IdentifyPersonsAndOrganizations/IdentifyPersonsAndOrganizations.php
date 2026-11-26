<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\IdentifyPersonsAndOrganizations;

use Prospecter\Core\PersonalData\Application\Identification\OrganizationClues;

/**
 * Finds which of the account's organizations and contacts the clues designate, in one call. Each person may name the
 * organization they work for by its position among the call's organizations. Nothing is written.
 */
final readonly class IdentifyPersonsAndOrganizations
{
    /**
     * @param list<PersonAtOrganization> $persons
     * @param list<OrganizationClues>    $organizations
     */
    public function __construct(
        public string $readerId,
        public array $persons,
        public array $organizations,
    ) {
    }
}

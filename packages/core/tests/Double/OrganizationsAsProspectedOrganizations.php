<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Domain\ProspectedOrganizations;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The organizations PersonalData asks about read from Prospecting, like the anticorruption layer of the API.
 */
final readonly class OrganizationsAsProspectedOrganizations implements ProspectedOrganizations
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    public function isProspectedBy(OrganizationId $organization, AccountId $accountId): bool
    {
        try {
            return $this->organizations->get($organization)->isProspectedBy($accountId);
        } catch (UnknownOrganization) {
            return false;
        }
    }
}

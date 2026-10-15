<?php

namespace App\PersonalData;

use Prospecter\Core\PersonalData\Domain\ProspectedOrganizations;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Anticorruption layer: PersonalData asks whether an organization is one the account prospects, Prospecting
 * keeps the organizations (ADR 0042). The only class of PersonalData that sees Prospecting.
 */
final readonly class ProspectingOrganizations implements ProspectedOrganizations
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

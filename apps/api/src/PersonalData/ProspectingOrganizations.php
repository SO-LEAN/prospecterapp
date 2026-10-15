<?php

namespace App\PersonalData;

use Prospecter\Core\PersonalData\Domain\ProspectedOrganizations;
use Prospecter\Core\Prospecting\Application\Published\IsProspectedBy\IsProspectedBy;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Anticorruption layer (ADR 0045): PersonalData asks Prospecting, through its published query, whether
 * an organization is one the account prospects; an unknown organization is prospected by nobody.
 */
final readonly class ProspectingOrganizations implements ProspectedOrganizations
{
    public function __construct(private RefusalTranslatingHandler $isProspectedBy)
    {
    }

    public function isProspectedBy(OrganizationId $organization, AccountId $accountId): bool
    {
        try {
            return ($this->isProspectedBy)(new IsProspectedBy((string) $organization, (string) $accountId));
        } catch (UseCaseRefused) {
            return false;
        }
    }
}

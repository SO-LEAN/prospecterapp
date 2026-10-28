<?php

namespace App\PersonalData;

use Prospecter\Core\PersonalData\Domain\Port\ProspectedOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\IsProspectedBy\IsProspectedBy;
use Prospecter\Core\Prospecting\Contract\Service\IsProspectedBy\KeptIsProspected;
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
        $kept = new KeptIsProspected();

        try {
            ($this->isProspectedBy)(new IsProspectedBy((string) $organization, (string) $accountId), $kept);
        } catch (UseCaseRefused) {
            return false;
        }

        return $kept->isProspected;
    }
}

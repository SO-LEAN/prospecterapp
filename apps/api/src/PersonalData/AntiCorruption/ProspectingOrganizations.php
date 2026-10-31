<?php

namespace App\PersonalData\AntiCorruption;

use Prospecter\Core\PersonalData\Domain\Port\ProspectedOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\KeptProspected;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspected;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspectedService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Anticorruption layer (ADR 0045, 0089): PersonalData knows the organizations a contact may belong to,
 * Prospecting tells which organizations an account prospects, once for all of them.
 */
final readonly class ProspectingOrganizations implements ProspectedOrganizations
{
    public function __construct(private WhichAreProspectedService $whichAreProspectedService)
    {
    }

    /**
     * @param list<OrganizationId> $organizations
     */
    public function prospectedAmong(array $organizations, AccountId $accountId): array
    {
        $kept = new KeptProspected();
        ($this->whichAreProspectedService)(new WhichAreProspected(array_map(static fn (OrganizationId $organization): string => $organization->value, $organizations), $accountId->value), $kept);

        return array_map(static fn (string $id): OrganizationId => new OrganizationId($id), $kept->organizationIds);
    }
}

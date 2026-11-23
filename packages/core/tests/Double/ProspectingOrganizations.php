<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Domain\Model\OrganizationId;
use Prospecter\Core\PersonalData\Domain\Port\ProspectedOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\KeptProspected;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspected;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspectedService;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089). PersonalData knows the organizations that a
 * contact may belong to. Prospecting tells which of them the account prospects, in one call for all of them.
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

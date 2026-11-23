<?php

declare(strict_types=1);

namespace App\Campaigns\AntiCorruption;

use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\Campaigns\Domain\Port\TaggableOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\KeptProspected;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspected;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspectedService;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Anticorruption layer (ADR 0045, 0089). Campaigns tags the organizations that Prospecting says an account prospects.
 * It asks about a set of one.
 */
final readonly class ProspectingTaggableOrganizations implements TaggableOrganizations
{
    public function __construct(private WhichAreProspectedService $whichAreProspectedService)
    {
    }

    public function isProspectedBy(OrganizationId $organization, AccountId $accountId): bool
    {
        $kept = new KeptProspected();
        ($this->whichAreProspectedService)(new WhichAreProspected([$organization->value], $accountId->value), $kept);

        return in_array($organization->value, $kept->organizationIds, true);
    }
}

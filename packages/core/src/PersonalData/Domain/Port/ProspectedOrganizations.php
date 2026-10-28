<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Port: whether an organization is one the account prospects, the only place a contact may belong to.
 * Prospecting keeps the organizations; the API answers through an anticorruption adapter (ADR 0042).
 */
interface ProspectedOrganizations
{
    public function isProspectedBy(OrganizationId $organization, AccountId $accountId): bool;
}

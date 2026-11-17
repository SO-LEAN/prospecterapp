<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Port: whether an account prospects an organization, asked of Prospecting through its published service
 * `WhichAreProspectedService` (ADR 0045, 0089).
 */
interface TaggableOrganizations
{
    public function isProspectedBy(OrganizationId $organization, AccountId $accountId): bool;
}

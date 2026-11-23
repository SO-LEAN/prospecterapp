<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: whether an account prospects an organization (ADR 0045, 0089). It asks Prospecting through its published
 * service `WhichAreProspectedService`.
 */
interface TaggableOrganizations
{
    public function isProspectedBy(OrganizationId $organization, AccountId $accountId): bool;
}

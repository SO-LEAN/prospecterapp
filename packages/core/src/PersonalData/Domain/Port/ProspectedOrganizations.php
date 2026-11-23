<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Port;

use Prospecter\Core\PersonalData\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: which organizations the account prospects. A contact may belong to these organizations only. Prospecting keeps
 * the organizations. The API answers through an anticorruption adapter, which asks once for all of them (ADR 0042,
 * 0089).
 */
interface ProspectedOrganizations
{
    /**
     * @param list<OrganizationId> $organizations
     *
     * @return list<OrganizationId> the organizations asked that the account prospects
     */
    public function prospectedAmong(array $organizations, AccountId $accountId): array;
}

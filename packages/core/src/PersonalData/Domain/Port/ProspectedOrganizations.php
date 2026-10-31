<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Port: which organizations the account prospects, the only places a contact may belong to. Prospecting keeps
 * the organizations; the API answers through an anticorruption adapter, asking once for all of them (ADR 0042,
 * 0089).
 */
interface ProspectedOrganizations
{
    /**
     * @param list<OrganizationId> $organizations
     *
     * @return list<OrganizationId> those, among the organizations asked, the account prospects
     */
    public function prospectedAmong(array $organizations, AccountId $accountId): array;
}

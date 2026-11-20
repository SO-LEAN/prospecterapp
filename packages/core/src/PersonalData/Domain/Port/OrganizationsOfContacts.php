<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: the organizations naming contacts of the account. Prospecting keeps where a contact works; the API answers
 * through an anticorruption adapter, asking once for all of them (ADR 0089).
 */
interface OrganizationsOfContacts
{
    /**
     * @param list<PersonId> $contacts
     *
     * @return array<string, list<OrganizationId>> by person id, those no organization names absent
     */
    public function organizationsOf(array $contacts, AccountId $accountId): array;
}

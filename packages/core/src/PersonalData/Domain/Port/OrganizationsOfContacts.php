<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: the organizations that name the account's contacts. Prospecting keeps where a contact works. The API answers
 * through an anticorruption adapter, which asks once for all the contacts (ADR 0089).
 */
interface OrganizationsOfContacts
{
    /**
     * @param list<PersonId> $contacts
     *
     * @return array<string, list<OrganizationId>> keyed by person id; a contact no organization names is absent
     */
    public function organizationsOf(array $contacts, AccountId $accountId): array;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Port;

use Prospecter\Core\PersonalData\Domain\Model\OrganizationId;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

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

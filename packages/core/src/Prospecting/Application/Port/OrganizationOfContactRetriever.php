<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the organizations of an account naming each contact, without restoring them (ADR 0088).
 */
interface OrganizationOfContactRetriever
{
    /**
     * @param list<PersonId> $contacts
     *
     * @return array<string, list<OrganizationOfContact>> by person id, every asked person included
     */
    public function ofContacts(array $contacts, AccountId $accountId): array;
}

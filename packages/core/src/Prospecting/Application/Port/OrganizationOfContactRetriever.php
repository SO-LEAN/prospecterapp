<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads which of an account's organizations name each contact, without restoring them (ADR 0088).
 */
interface OrganizationOfContactRetriever
{
    /**
     * @param list<ContactId> $contacts
     *
     * @return array<string, list<OrganizationOfContact>> by person id, with every person asked for
     */
    public function ofContacts(array $contacts, AccountId $accountId): array;
}

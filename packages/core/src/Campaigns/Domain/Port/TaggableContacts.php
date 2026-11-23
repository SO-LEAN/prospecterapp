<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: which people in a set are contacts of an account (ADR 0045, 0089). It asks PersonalData through its published
 * service `WhichAreContactsService`.
 */
interface TaggableContacts
{
    /**
     * @param list<ContactId> $persons
     *
     * @return list<ContactId> the people who are contacts of the account
     */
    public function contactsAmong(array $persons, AccountId $accountId): array;
}

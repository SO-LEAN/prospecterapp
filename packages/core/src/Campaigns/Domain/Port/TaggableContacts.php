<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: which persons are contacts of an account, asked of PersonalData about a set through its published service
 * `WhichAreContactsService` (ADR 0045, 0089).
 */
interface TaggableContacts
{
    /**
     * @param list<PersonId> $persons
     *
     * @return list<PersonId> those of the persons that are contacts of the account
     */
    public function contactsAmong(array $persons, AccountId $accountId): array;
}

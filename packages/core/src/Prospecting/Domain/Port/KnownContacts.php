<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Port: whether the account prospects a person. PersonalData keeps the persons, and Prospecting never sees them. The
 * API answers through an anticorruption adapter (ADR 0041). A person PersonalData forgot is no longer a contact.
 */
interface KnownContacts
{
    public function isAContactOf(ContactId $person, AccountId $accountId): bool;

    /**
     * @param list<ContactId> $persons
     *
     * @return list<ContactId> those of the persons that are the account's contacts, in their order
     */
    public function whichAreContactsOf(array $persons, AccountId $accountId): array;
}

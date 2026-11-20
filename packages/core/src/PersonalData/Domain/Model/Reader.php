<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A member of an account as personal data sees them: who reads, for which account, which person they are,
 * and whether they hold the right to read personal data in clear.
 */
final readonly class Reader
{
    public function __construct(
        public MemberId $id,
        public AccountId $accountId,
        public PersonId $personId,
        public bool $readsPersonalData,
    ) {
    }

    /**
     * Every member searches the contacts, by their name at least (ADR 0129).
     *
     * @return list<ContactsIntent>
     */
    public function contactsIntents(): array
    {
        return ContactsIntent::cases();
    }

    /**
     * A reader of masked emails could guess them by searching in what they cannot read: they search the names only
     * (ADR 0129).
     */
    public function searchesTheEmails(): bool
    {
        return $this->readsPersonalData;
    }

    /**
     * Identifying a person by their links, their emails or their phone numbers reads them: a reader of masked data
     * could guess what they cannot read, and identifies a person by their name only.
     */
    public function identifiesByPersonalData(): bool
    {
        return $this->readsPersonalData;
    }

    /**
     * Recognizing a person by their phone number reads it: a reader of masked data may not.
     *
     * @throws PersonalDataManagersOnly
     */
    public function ensureMayRecognizeByPhoneNumber(): void
    {
        if (!$this->readsPersonalData) {
            throw PersonalDataManagersOnly::because('Only a member allowed to manage personal data may recognize a person by their phone number', 'phoneNumber');
        }
    }
}

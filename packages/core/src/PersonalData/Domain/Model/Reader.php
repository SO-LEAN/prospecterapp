<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A member of an account, as PersonalData sees them: who reads, for which account, which person they are, and whether
 * they hold the right to read personal data unmasked.
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
     * Every member searches the contacts, at least by their name (ADR 0129).
     *
     * @return list<ContactsIntent>
     */
    public function contactsIntents(): array
    {
        return ContactsIntent::cases();
    }

    /**
     * A reader of masked emails could guess them by searching in what they cannot read. So they search the names only
     * (ADR 0129).
     */
    public function searchesTheEmails(): bool
    {
        return $this->readsPersonalData;
    }

    /**
     * Identifying a person by their links, emails or phone numbers reads them. A reader of masked data could guess what
     * they cannot read, so they identify a person by name only.
     */
    public function identifiesByPersonalData(): bool
    {
        return $this->readsPersonalData;
    }

    /**
     * Recognizing a person by their phone number reads that number. A reader of masked data may not do it.
     *
     * @throws PersonalDataManagersOnly
     */
    public function ensureMayRecognizeByPhoneNumber(): void
    {
        if (!$this->readsPersonalData) {
            throw PersonalDataManagersOnly::because('Only a member allowed to manage personal data can recognize a person by their phone number', 'phoneNumber');
        }
    }
}

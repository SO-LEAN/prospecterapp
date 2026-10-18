<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;
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
     * Searching contacts by text needs the right to manage personal data; reading them masked does not.
     *
     * @return list<ContactsIntent>
     */
    public function contactsIntents(): array
    {
        return $this->readsPersonalData ? ContactsIntent::cases() : [];
    }

    /**
     * A reader of masked data could guess them by filtering on what they cannot read.
     *
     * @throws NotAllowed
     */
    public function ensureMaySearchByText(string $text): void
    {
        if ('' !== $text && !$this->readsPersonalData) {
            throw NotAllowed::because('Only a member allowed to manage personal data may search persons by text', 'q');
        }
    }

    /**
     * Recognizing a person by their phone number reads it: a reader of masked data may not.
     *
     * @throws NotAllowed
     */
    public function ensureMayRecognizeByPhoneNumber(): void
    {
        if (!$this->readsPersonalData) {
            throw NotAllowed::because('Only a member allowed to manage personal data may recognize a person by their phone number', 'phoneNumber');
        }
    }
}

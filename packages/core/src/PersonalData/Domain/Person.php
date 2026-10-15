<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\NotAllowed;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Aggregate root: the personal data of a natural person, kept for one account and one purpose. The account
 * collected them: another account does not even learn that the person exists (ADR 0031).
 */
class Person
{
    private ?File $picture = null;

    private function __construct(
        private readonly PersonId $id,
        private readonly AccountId $accountId,
        private readonly Purpose $purpose,
        private PersonName $name,
        private ContactDetails $contactDetails,
    ) {
    }

    public static function collect(PersonId $id, AccountId $accountId, Purpose $purpose, PersonName $name = new PersonName(), ContactDetails $contactDetails = new ContactDetails()): self
    {
        return new self($id, $accountId, $purpose, $name, $contactDetails);
    }

    public function id(): PersonId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function purpose(): Purpose
    {
        return $this->purpose;
    }

    public function name(): PersonName
    {
        return $this->name;
    }

    public function contactDetails(): ContactDetails
    {
        return $this->contactDetails;
    }

    public function picture(): ?File
    {
        return $this->picture;
    }

    /**
     * In clear for the person themself and for a member of the account with the right to read personal data,
     * masked for the other members of the account.
     *
     * @throws UnknownPerson for anybody else
     */
    public function readingFor(Reader $reader): Reading
    {
        $this->ensureReachableBy($reader);

        return $this->isThePersonOf($reader) || $reader->readsPersonalData ? Reading::Clear : Reading::Masked;
    }

    /**
     * A person changes their own data; a member with the right changes the persons their account prospects,
     * not the profile of a colleague. Every intention refuses exactly what is missing here.
     *
     * @return list<PersonIntent>
     */
    public function possibleIntentsOf(Reader $reader): array
    {
        return $this->belongsTo($reader) && $this->mayBeChangedBy($reader) ? PersonIntent::cases() : [];
    }

    public function rename(PersonName $name, Reader $by): void
    {
        $this->ensurePossible($by);
        $this->name = $name;
    }

    public function changeContactDetails(ContactDetails $contactDetails, Reader $by): void
    {
        $this->ensurePossible($by);
        $this->contactDetails = $contactDetails;
    }

    public function changePicture(File $picture, Reader $by): void
    {
        $this->ensurePossible($by);
        $this->picture = $picture;
    }

    private function belongsTo(Reader $reader): bool
    {
        return $this->accountId->equals($reader->accountId);
    }

    private function isThePersonOf(Reader $reader): bool
    {
        return $this->id->equals($reader->personId);
    }

    private function mayBeChangedBy(Reader $reader): bool
    {
        return $this->isThePersonOf($reader) || (Purpose::Prospecting === $this->purpose && $reader->readsPersonalData);
    }

    private function ensureReachableBy(Reader $reader): void
    {
        if (!$this->belongsTo($reader)) {
            throw new UnknownPerson($this->id);
        }
    }

    private function ensurePossible(Reader $by): void
    {
        $this->ensureReachableBy($by);

        if (!$this->mayBeChangedBy($by)) {
            throw new NotAllowed('Only the person or a member allowed to manage personal data may change them');
        }
    }
}

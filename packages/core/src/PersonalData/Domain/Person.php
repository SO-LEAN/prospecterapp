<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Aggregate root: the personal data of a natural person, kept for one account and one purpose. The account
 * collected them: another account does not even learn that the person exists (ADR 0031).
 */
class Person
{
    private ?File $picture = null;
    private ?string $jobTitle = null;
    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly PersonId $id,
        private readonly AccountId $accountId,
        private readonly Purpose $purpose,
        private PersonName $name,
        private ContactDetails $contactDetails,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    public static function collect(PersonId $id, AccountId $accountId, Purpose $purpose, \DateTimeImmutable $at, PersonName $name = new PersonName(), ContactDetails $contactDetails = new ContactDetails()): self
    {
        return new self($id, $accountId, $purpose, $name, $contactDetails, $at);
    }

    /**
     * A person the account of the member prospects, collected by a member with the right to manage personal
     * data (ADR 0041).
     *
     * @throws NotAllowed for a member without the right
     */
    public static function collectAsContact(Reader $by, PersonId $id, PersonName $name, ?string $jobTitle, ContactDetails $contactDetails, \DateTimeImmutable $at): self
    {
        if (!$by->readsPersonalData) {
            throw NotAllowed::because('Only a member allowed to manage personal data may collect a contact');
        }

        $contact = new self($id, $by->accountId, Purpose::Prospecting, $name, $contactDetails, $at);
        $contact->jobTitle = $jobTitle;

        return $contact;
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

    /**
     * What the person does in the organization they work for.
     */
    public function jobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function contactDetails(): ContactDetails
    {
        return $this->contactDetails;
    }

    public function picture(): ?File
    {
        return $this->picture;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * When an intention last changed it: its creation, then every change.
     */
    public function modifiedAt(): \DateTimeImmutable
    {
        return $this->modifiedAt;
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
     * not the profile of a colleague, and only a prospected person is forgotten. Every intention refuses
     * exactly what is missing here.
     *
     * @return list<PersonIntent>
     */
    public function possibleIntentsOf(Reader $reader): array
    {
        return array_values(array_filter(PersonIntent::cases(), fn (PersonIntent $intent): bool => $this->belongsTo($reader) && $this->allows($intent, $reader)));
    }

    public function rename(PersonName $name, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::Rename, $by);
        $this->name = $name;
        $this->modifiedAt = $at;
    }

    public function changeJobTitle(?string $jobTitle, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::ChangeJobTitle, $by);
        $this->jobTitle = $jobTitle;
        $this->modifiedAt = $at;
    }

    public function changeContactDetails(ContactDetails $contactDetails, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::ChangeContactDetails, $by);
        $this->contactDetails = $contactDetails;
        $this->modifiedAt = $at;
    }

    public function changePicture(File $picture, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::ChangePicture, $by);
        $this->picture = $picture;
        $this->modifiedAt = $at;
    }

    /**
     * Checks that this reader may erase the person; the repository then removes it.
     *
     * @throws UnknownPerson|NotAllowed
     */
    public function ensureMayBeForgottenBy(Reader $by): void
    {
        $this->ensurePossible(PersonIntent::Forget, $by);
    }

    private function belongsTo(Reader $reader): bool
    {
        return $this->accountId->equals($reader->accountId);
    }

    private function isThePersonOf(Reader $reader): bool
    {
        return $this->id->equals($reader->personId);
    }

    private function allows(PersonIntent $intent, Reader $reader): bool
    {
        return PersonIntent::Forget === $intent ? $this->isAContactManagedBy($reader) : $this->isThePersonOf($reader) || $this->isAContactManagedBy($reader);
    }

    private function isAContactManagedBy(Reader $reader): bool
    {
        return Purpose::Prospecting === $this->purpose && $reader->readsPersonalData;
    }

    private function ensureReachableBy(Reader $reader): void
    {
        if (!$this->belongsTo($reader)) {
            throw UnknownPerson::identifiedBy($this->id);
        }
    }

    private function ensurePossible(PersonIntent $intent, Reader $by): void
    {
        $this->ensureReachableBy($by);

        if (!$this->allows($intent, $by)) {
            throw NotAllowed::because(PersonIntent::Forget === $intent ? 'Only a member allowed to manage personal data may forget a prospected person' : 'Only the person or a member allowed to manage personal data may change them');
        }
    }
}

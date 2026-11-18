<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\PersonalData\Domain\Event\CivilityChanged;
use Prospecter\Core\PersonalData\Domain\Event\ContactCollected;
use Prospecter\Core\PersonalData\Domain\Event\ContactForgotten;
use Prospecter\Core\PersonalData\Domain\Event\JobTitleChanged;
use Prospecter\Core\PersonalData\Domain\Event\PersonContactDetailsChanged;
use Prospecter\Core\PersonalData\Domain\Event\PersonPictureChanged;
use Prospecter\Core\PersonalData\Domain\Event\PersonRenamed;
use Prospecter\Core\PersonalData\Domain\Exception\ContactWithoutOrganization;
use Prospecter\Core\PersonalData\Domain\Exception\ThePersonOrAPersonalDataManagerOnly;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Policy\PersonPossibilities;
use Prospecter\Core\PersonalData\Domain\Policy\PersonReading;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;

/**
 * Aggregate root: the personal data of a natural person, kept for one account and one purpose. The account
 * collected them: another account does not even learn that the person exists (ADR 0031).
 */
final class Person implements ReleasesEvents
{
    use RecordsEvents;

    private ?File $picture = null;
    private ?string $jobTitle = null;
    private Civility $civility = Civility::X;
    private ?LinkedInProfile $linkedInProfile = null;
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
     * data, for at least one of the organizations it prospects: a contact is never alone (ADR 0042).
     *
     * @param list<OrganizationId> $organizations where the person works, prospected by the account
     *
     * @throws PersonalDataManagersOnly
     * @throws ContactWithoutOrganization
     */
    public static function collectAsContact(Reader $by, PersonId $id, PersonName $name, Civility $civility, ?string $jobTitle, ContactDetails $contactDetails, ?LinkedInProfile $linkedInProfile, array $organizations, \DateTimeImmutable $at): self
    {
        self::ensureCollectedByAManager($by);

        if ([] === $organizations) {
            throw ContactWithoutOrganization::because('A contact belongs to at least one organization', 'organizationIds');
        }

        $contact = new self($id, $by->accountId, Purpose::Prospecting, $name, $contactDetails, $at);
        $contact->civility = $civility;
        $contact->jobTitle = $jobTitle;
        $contact->linkedInProfile = $linkedInProfile;
        $contact->recordThat(new ContactCollected($by->accountId, $by->id, $id, $organizations, $at));

        return $contact;
    }

    /**
     * A person who took a meeting with the member before anyone knows for which organization they work: a contact
     * of the account waiting to be attached (ADR 0146), collected by a member with the right to manage personal
     * data like any contact.
     *
     * @throws PersonalDataManagersOnly
     */
    public static function collectToAttach(Reader $by, PersonId $id, PersonName $name, ContactDetails $contactDetails, \DateTimeImmutable $at): self
    {
        self::ensureCollectedByAManager($by);
        $contact = new self($id, $by->accountId, Purpose::Prospecting, $name, $contactDetails, $at);
        $contact->recordThat(new ContactCollected($by->accountId, $by->id, $id, [], $at));

        return $contact;
    }

    /**
     * @throws PersonalDataManagersOnly
     */
    private static function ensureCollectedByAManager(Reader $by): void
    {
        if (!$by->readsPersonalData) {
            throw PersonalDataManagersOnly::because('Only a member allowed to manage personal data may collect a contact');
        }
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

    public function isAContactOf(AccountId $accountId): bool
    {
        return $this->accountId->equals($accountId) && Purpose::Prospecting === $this->purpose;
    }

    public function contactDetails(): ContactDetails
    {
        return $this->contactDetails;
    }

    public function civility(): Civility
    {
        return $this->civility;
    }

    public function linkedInProfile(): ?LinkedInProfile
    {
        return $this->linkedInProfile;
    }

    /**
     * A person is found by the beginning of their last name, of their first name or of their email.
     */
    public function searchKeys(): SearchKeys
    {
        return new SearchKeys($this->name->lastName, $this->contactDetails->email, $this->name->firstName);
    }

    public function rename(PersonName $name, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::Rename, $by);
        $this->name = $name;
        $this->modifiedAt = $at;
        $this->recordThat(new PersonRenamed($this->accountId, $by->id, $this->id, $at));
    }

    public function changeJobTitle(?string $jobTitle, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::ChangeJobTitle, $by);
        $this->jobTitle = $jobTitle;
        $this->modifiedAt = $at;
        $this->recordThat(new JobTitleChanged($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * Only for a contact: the civility of the profile of a member is not noted.
     */
    public function changeCivility(Civility $civility, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::ChangeCivility, $by);
        $this->civility = $civility;
        $this->modifiedAt = $at;
        $this->recordThat(new CivilityChanged($this->accountId, $by->id, $this->id, $at));
    }

    public function changeContactDetails(ContactDetails $contactDetails, ?LinkedInProfile $linkedInProfile, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::ChangeContactDetails, $by);
        $this->contactDetails = $contactDetails;
        $this->linkedInProfile = $linkedInProfile;
        $this->modifiedAt = $at;
        $this->recordThat(new PersonContactDetailsChanged($this->accountId, $by->id, $this->id, $at));
    }

    public function changePicture(File $picture, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::ChangePicture, $by);
        $this->picture = $picture;
        $this->modifiedAt = $at;
        $this->recordThat(new PersonPictureChanged($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * Erases a prospected person: once the repository removed it, no organization may name it any more
     * (ContactForgotten).
     *
     * @throws UnknownPerson|NotAllowed
     */
    public function forget(Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::Forget, $by);
        $this->recordThat(new ContactForgotten($this->accountId, $by->id, $this->id, $at));
    }

    private function ensureReachableBy(Reader $reader): void
    {
        if (!PersonReading::isReachableBy($reader, $this->accountId)) {
            throw UnknownPerson::identifiedBy($this->id);
        }
    }

    private function ensurePossible(PersonIntent $intent, Reader $by): void
    {
        $this->ensureReachableBy($by);

        if (!PersonPossibilities::allows($intent, $by, $this->id, $this->purpose)) {
            throw match ($intent) {
                PersonIntent::Forget => PersonalDataManagersOnly::because('Only a member allowed to manage personal data may forget a prospected person'),
                PersonIntent::ChangeCivility => PersonalDataManagersOnly::because('Only a member allowed to manage personal data may change the civility of a contact'),
                default => ThePersonOrAPersonalDataManagerOnly::because('Only the person or a member allowed to manage personal data may change them'),
            };
        }
    }
}

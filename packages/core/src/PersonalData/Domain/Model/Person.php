<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\PersonalData\Domain\Event\BookerIdentified;
use Prospecter\Core\PersonalData\Domain\Event\CivilityChanged;
use Prospecter\Core\PersonalData\Domain\Event\ContactCollected;
use Prospecter\Core\PersonalData\Domain\Event\ContactCompleted;
use Prospecter\Core\PersonalData\Domain\Event\ContactForgotten;
use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspected;
use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspicionDismissed;
use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspicionLifted;
use Prospecter\Core\PersonalData\Domain\Event\JobTitleChanged;
use Prospecter\Core\PersonalData\Domain\Event\PersonContactDetailsChanged;
use Prospecter\Core\PersonalData\Domain\Event\PersonPictureChanged;
use Prospecter\Core\PersonalData\Domain\Event\PersonRenamed;
use Prospecter\Core\PersonalData\Domain\Exception\ContactWithoutOrganization;
use Prospecter\Core\PersonalData\Domain\Exception\NotASuspectedDuplicateContact;
use Prospecter\Core\PersonalData\Domain\Exception\ThePersonOrAPersonalDataManagerOnly;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Policy\PersonPossibilities;
use Prospecter\Core\PersonalData\Domain\Policy\PersonReading;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Completion;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\MemberId;
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

    private const int FORMER_REFERENCES_KEPT = 3;

    private const string CIVILITY = 'civility';
    private const string JOB_TITLE = 'jobTitle';
    private const string EMAIL = 'email';
    private const string PHONE_NUMBER = 'phoneNumber';
    private const string LINKS = 'links';

    private ?File $picture = null;
    private ?string $jobTitle = null;
    private Civility $civility = Civility::X;
    private ExternalReferences $references;
    /** @var list<ExternalReference> */
    private array $formerReferences = [];
    /** @var list<PersonId> */
    private array $possibleDuplicates = [];
    /** @var list<PersonId> */
    private array $notDuplicates = [];
    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly PersonId $id,
        private readonly AccountId $accountId,
        private readonly Purpose $purpose,
        private PersonName $name,
        private ContactDetails $contactDetails,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->references = ExternalReferences::none();
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
    public static function collectAsContact(Reader $by, PersonId $id, PersonName $name, Civility $civility, ?string $jobTitle, ContactDetails $contactDetails, ExternalReferences $references, array $organizations, \DateTimeImmutable $at): self
    {
        self::ensureCollectedByAManager($by);

        if ([] === $organizations) {
            throw ContactWithoutOrganization::because('A contact belongs to at least one organization', 'organizationIds');
        }

        $contact = new self($id, $by->accountId, Purpose::Prospecting, $name, $contactDetails, $at);
        $contact->civility = $civility;
        $contact->jobTitle = $jobTitle;
        $contact->references = $references;
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

    /**
     * This contact booked an appointment with the host by their public link: the host meets them (ADR 0164).
     */
    public function identifyAsBookerOf(string $appointmentId, MemberId $host, \DateTimeImmutable $at): void
    {
        $this->recordThat(new BookerIdentified($this->accountId, $host, $this->id, $appointmentId, $at));
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

    public function name(): PersonName
    {
        return $this->name;
    }

    public function contactDetails(): ContactDetails
    {
        return $this->contactDetails;
    }

    public function civility(): Civility
    {
        return $this->civility;
    }

    /**
     * Their links to the tools outside.
     */
    public function references(): ExternalReferences
    {
        return $this->references;
    }

    public function holdsReference(ExternalReference $reference): bool
    {
        return array_any($this->referencesHeld(), static fn (ExternalReference $held): bool => $held->key() === $reference->key());
    }

    /**
     * Their links and the former ones, which still recognize them.
     *
     * @return list<ExternalReference>
     */
    public function referencesHeld(): array
    {
        return [...$this->references->all(), ...$this->formerReferences];
    }

    /**
     * A person is found by the beginning of their last name, of their first name or of their email.
     */
    public function searchKeys(): SearchKeys
    {
        return new SearchKeys($this->name->lastName, $this->contactDetails->email, $this->name->firstName);
    }

    /**
     * What a contact shares with those resembling them, none for the profile of a member nor without both names.
     */
    public function resemblanceKey(): ?PersonResemblanceKey
    {
        return Purpose::Prospecting === $this->purpose ? PersonResemblanceKey::of($this->name) : null;
    }

    /**
     * Suspected of being a duplicate of each contact resembling them, and no longer of the others. No member suspects:
     * the moment of modification stays.
     *
     * @param list<PersonId> $resembling
     */
    public function reconsiderDuplicates(array $resembling, MemberId $by, \DateTimeImmutable $at): void
    {
        foreach ($this->possibleDuplicates as $suspected) {
            if (!array_any($resembling, $suspected->equals(...))) {
                $this->liftSuspicionOf($suspected, $by, $at);
            }
        }

        foreach ($resembling as $duplicate) {
            $this->suspectDuplicateOf($duplicate, $by, $at);
        }
    }

    /**
     * Suspected of being a duplicate of this contact, once, unless a member checked they are not.
     */
    public function suspectDuplicateOf(PersonId $duplicate, MemberId $by, \DateTimeImmutable $at): void
    {
        if ($this->isSuspectedDuplicateOf($duplicate) || $this->isNotADuplicateOf($duplicate)) {
            return;
        }

        $this->possibleDuplicates[] = $duplicate;
        $this->recordThat(new DuplicateSuspected($this->accountId, $by, $this->id, $duplicate, $at));
    }

    /**
     * No longer suspected of being a duplicate of this contact; never suspected, nothing changes.
     */
    public function liftSuspicionOf(PersonId $duplicate, MemberId $by, \DateTimeImmutable $at): void
    {
        if (!$this->isSuspectedDuplicateOf($duplicate)) {
            return;
        }

        $this->possibleDuplicates = $this->withoutSuspicionOf($duplicate);
        $this->recordThat(new DuplicateSuspicionLifted($this->accountId, $by, $this->id, $duplicate, $at));
    }

    /**
     * Checked by a member, they are no duplicate of the contact they are suspected of being.
     *
     * @throws NotASuspectedDuplicateContact|UnknownPerson|NotAllowed
     */
    public function dismissSuspicionOf(PersonId $duplicate, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::DismissPossibleDuplicate, $by);

        if (!$this->isSuspectedDuplicateOf($duplicate)) {
            throw NotASuspectedDuplicateContact::because('The contact is not suspected of being a duplicate of this one', 'duplicateId');
        }

        $this->knowItIsNoDuplicateOf($duplicate, $by->id, $at);
    }

    /**
     * Checked by a member, they are no duplicate of this contact, suspected or not; known already, nothing changes.
     */
    public function knowItIsNoDuplicateOf(PersonId $duplicate, MemberId $by, \DateTimeImmutable $at): void
    {
        if ($this->isNotADuplicateOf($duplicate)) {
            return;
        }

        $this->notDuplicates[] = $duplicate;
        $this->possibleDuplicates = $this->withoutSuspicionOf($duplicate);
        $this->modifiedAt = $at;
        $this->recordThat(new DuplicateSuspicionDismissed($this->accountId, $by, $this->id, $duplicate, $at));
    }

    /**
     * @return list<PersonId>
     */
    private function withoutSuspicionOf(PersonId $duplicate): array
    {
        return array_values(array_filter($this->possibleDuplicates, static fn (PersonId $suspected): bool => !$suspected->equals($duplicate)));
    }

    private function isNotADuplicateOf(PersonId $contact): bool
    {
        return array_any($this->notDuplicates, $contact->equals(...));
    }

    private function isSuspectedDuplicateOf(PersonId $duplicate): bool
    {
        return array_any($this->possibleDuplicates, $duplicate->equals(...));
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

    public function changeContactDetails(ContactDetails $contactDetails, ExternalReferences $references, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::ChangeContactDetails, $by);
        $this->contactDetails = $contactDetails;
        $this->noteReferences($references);
        $this->modifiedAt = $at;
        $this->recordThat(new PersonContactDetailsChanged($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * Fills what the contact lacks, never changing what is noted (ADR 0177); nothing to fill, nothing happens.
     */
    public function completeWith(ContactSupplement $supplement, Reader $by, \DateTimeImmutable $at): void
    {
        $completion = $this->completionWith($supplement, $by);

        if (!$completion->completesAnything()) {
            return;
        }

        $this->civility = $completion->completes(self::CIVILITY) ? $supplement->civility : $this->civility;
        $this->jobTitle = $completion->completes(self::JOB_TITLE) ? $supplement->jobTitle : $this->jobTitle;
        $this->contactDetails = new ContactDetails(
            $completion->completes(self::EMAIL) ? $supplement->email : $this->contactDetails->email,
            $completion->completes(self::PHONE_NUMBER) ? $supplement->phoneNumber : $this->contactDetails->phoneNumber,
            $this->contactDetails->language,
        );
        $this->references = ExternalReferences::of([...$this->references->all(), ...$this->linksToAdd($supplement)]);
        $this->modifiedAt = $at;
        $this->recordThat(new ContactCompleted($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * What the supplement would fill of the contact, and what it gives otherwise than noted.
     */
    public function completionWith(ContactSupplement $supplement, Reader $by): Completion
    {
        $this->ensurePossible(PersonIntent::Complete, $by);
        $linksNotHeld = $this->linksNotHeld($supplement);
        $linksToAdd = $this->linksToAdd($supplement);

        return new Completion()
            ->with(self::CIVILITY, self::noted($this->civility), self::noted($supplement->civility))
            ->with(self::JOB_TITLE, $this->jobTitle, $supplement->jobTitle)
            ->with(self::EMAIL, $this->contactDetails->email?->value, $supplement->email?->value)
            ->with(self::PHONE_NUMBER, $this->contactDetails->phoneNumber?->value, $supplement->phoneNumber?->value)
            ->withSome(self::LINKS, filled: count($linksToAdd), left: count($linksNotHeld) - count($linksToAdd));
    }

    private static function noted(Civility $civility): ?string
    {
        return Civility::X === $civility ? null : $civility->value;
    }

    /**
     * The links given they do not hold yet, each once, while there is room for them.
     *
     * @return list<ExternalReference>
     */
    private function linksToAdd(ContactSupplement $supplement): array
    {
        return array_slice($this->linksNotHeld($supplement), 0, ExternalReferences::MAX - count($this->references->all()));
    }

    /**
     * @return list<ExternalReference>
     */
    private function linksNotHeld(ContactSupplement $supplement): array
    {
        $notHeld = [];

        foreach ($supplement->links as $link) {
            if (!$this->references->holds($link)) {
                $notHeld[$link->key()] ??= $link;
            }
        }

        return array_values($notHeld);
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
     * (ContactForgotten), nor a contact suspect them of being their duplicate.
     *
     * @throws UnknownPerson|NotAllowed
     */
    public function forget(Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::Forget, $by);

        foreach ($this->possibleDuplicates as $suspected) {
            $this->liftSuspicionOf($suspected, $by->id, $at);
        }

        $this->recordThat(new ContactForgotten($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * The references they held but those noted now, the most recent first, the last ones only, whatever their source:
     * a contact forgotten costs a write per reference it holds.
     */
    private function noteReferences(ExternalReferences $references): void
    {
        $formers = array_filter($this->referencesHeld(), static fn (ExternalReference $held): bool => !$references->holds($held));
        $this->formerReferences = array_slice(array_values($formers), 0, self::FORMER_REFERENCES_KEPT);
        $this->references = $references;
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
                PersonIntent::DismissPossibleDuplicate => PersonalDataManagersOnly::because('Only a member allowed to manage personal data may say a contact is no duplicate'),
                PersonIntent::Complete => PersonalDataManagersOnly::because('Only a member allowed to manage personal data may complete a contact'),
                default => ThePersonOrAPersonalDataManagerOnly::because('Only the person or a member allowed to manage personal data may change them'),
            };
        }
    }
}

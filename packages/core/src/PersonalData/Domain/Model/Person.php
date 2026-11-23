<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\PersonalData\Domain\Event\BookerIdentified;
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
use Prospecter\Core\PersonalData\Domain\Event\TitleChanged;
use Prospecter\Core\PersonalData\Domain\Exception\ContactWithoutOrganization;
use Prospecter\Core\PersonalData\Domain\Exception\NotASuspectedDuplicateContact;
use Prospecter\Core\PersonalData\Domain\Exception\ThePersonOrAPersonalDataManagerOnly;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Rule\PersonPossibilities;
use Prospecter\Core\PersonalData\Domain\Rule\PersonReading;
use Prospecter\Core\SharedKernel\Domain\Completion\Completion;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Event\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\Event\ReleasesEvents;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\File\File;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Gesture\Origin;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKeys;

/**
 * Aggregate root: a natural person's personal data, kept for one account and one purpose. The account collected them.
 * Another account does not even learn that the person exists (ADR 0031).
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
    private Origin $origin = Origin::Hand;
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

    /**
     * The gesture names the account that keeps the person and the member who brought the person in. That member is the
     * administrator who registers a newcomer, or the founder who opens the account.
     */
    public static function collect(Gesture $gesture, PersonId $id, Purpose $purpose, ContactCard $card = new ContactCard()): self
    {
        return self::carded($id, $gesture->accountId, $purpose, $card, $gesture->at);
    }

    /**
     * Collects a person the member's account prospects, as a contact. Only a member with the right to manage personal
     * data collects one. The contact works in at least one of the organizations the account prospects: a contact is
     * never alone (ADR 0042). The member collects the contact by hand, or an AI collects it on the member's behalf.
     *
     * @throws PersonalDataManagersOnly
     * @throws ContactWithoutOrganization
     */
    public static function collectAsContact(Reader $by, NewContact $contact, \DateTimeImmutable $at, Origin $origin = Origin::Hand): self
    {
        self::ensureCollectedByAManager($by);

        if ([] === $contact->organizations) {
            throw ContactWithoutOrganization::because('A contact belongs to at least one organization', 'organizationIds');
        }

        $collected = self::carded($contact->id, $by->accountId, Purpose::Prospecting, $contact->card, $at);
        $collected->origin = $origin;
        $collected->recordThat(new ContactCollected($by->accountId, $by->id, $contact->id, $contact->organizations, $at));

        return $collected;
    }

    /**
     * Collects a person who met the member before anyone knows which organization they work for. The person is a
     * contact of the account, waiting to be attached (ADR 0146). As for any contact, only a member with the right to
     * manage personal data collects it.
     *
     * @throws PersonalDataManagersOnly
     */
    public static function collectToAttach(Reader $by, PersonId $id, ContactCard $card, \DateTimeImmutable $at): self
    {
        self::ensureCollectedByAManager($by);
        $contact = self::carded($id, $by->accountId, Purpose::Prospecting, $card, $at);
        $contact->recordThat(new ContactCollected($by->accountId, $by->id, $id, [], $at));

        return $contact;
    }

    private static function carded(PersonId $id, AccountId $accountId, Purpose $purpose, ContactCard $card, \DateTimeImmutable $at): self
    {
        $person = new self($id, $accountId, $purpose, $card->name, $card->contactDetails, $at);
        $person->civility = $card->civility;
        $person->jobTitle = $card->jobTitle;
        $person->references = $card->references;

        return $person;
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
     * This contact booked an appointment with the host through the host's public link. The host meets them (ADR 0164).
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
     * Their links to outside tools.
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
     * Their current links and their former ones. Both still recognize them.
     *
     * @return list<ExternalReference>
     */
    public function referencesHeld(): array
    {
        return [...$this->references->all(), ...$this->formerReferences];
    }

    /**
     * A search finds the person by the beginning of their last name, their first name or their email.
     */
    public function searchKeys(): SearchKeys
    {
        return new SearchKeys($this->name->lastName, $this->contactDetails->email, $this->name->firstName);
    }

    /**
     * What a contact shares with the contacts who resemble them. A member's profile has none, and neither has a contact
     * without both names.
     */
    public function resemblanceKey(): ?PersonResemblanceKey
    {
        return Purpose::Prospecting === $this->purpose ? PersonResemblanceKey::of($this->name) : null;
    }

    /**
     * Suspects the contact of being a duplicate of each contact who resembles them, and no longer of the others. No
     * member suspects anything here, so the modification time stays the same.
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
     * Suspects the contact, once, of being this contact's duplicate. Nothing happens if a member already checked that
     * they are not.
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
     * Lifts the suspicion that the contact is this contact's duplicate. Nothing changes if the contact was never
     * suspected.
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
     * A member checked that the contact is not a duplicate of the contact they are suspected of being.
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
     * A member checked that the contact is not this contact's duplicate, suspected or not. Nothing changes when this is
     * already known.
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
     * Only for a contact. A member's profile does not log a title.
     */
    public function changeCivility(Civility $civility, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::ChangeCivility, $by);
        $this->civility = $civility;
        $this->modifiedAt = $at;
        $this->recordThat(new TitleChanged($this->accountId, $by->id, $this->id, $at));
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
     * Fills what the contact lacks, and never changes what is logged (ADR 0177). Nothing happens when there is nothing
     * to fill.
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
        $this->references = ExternalReferences::of([...$this->references->all(), ...$this->references->roomFor($supplement->links)]);
        $this->modifiedAt = $at;
        $this->recordThat(new ContactCompleted($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * What the supplement would fill in the contact, and what it gives with a value other than the logged one.
     */
    public function completionWith(ContactSupplement $supplement, Reader $by): Completion
    {
        $this->ensurePossible(PersonIntent::Complete, $by);
        $linksNotHeld = $this->references->notHeldAmong($supplement->links);
        $linksToAdd = $this->references->roomFor($supplement->links);

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

    public function changePicture(File $picture, Reader $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(PersonIntent::ChangePicture, $by);
        $this->picture = $picture;
        $this->modifiedAt = $at;
        $this->recordThat(new PersonPictureChanged($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * Erases a prospected person. Once the repository removed the person, no organization may name them any more
     * (ContactForgotten). No contact may suspect them of being a duplicate either.
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
     * Keeps the references the person held, except the ones logged now, as former references. It keeps only the last
     * ones, the most recent first, whatever their source. Each reference a forgotten contact holds costs one write.
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
                PersonIntent::Complete => PersonalDataManagersOnly::because('Only a member allowed to manage personal data can complete a contact'),
                default => ThePersonOrAPersonalDataManagerOnly::because('Only the person or a member allowed to manage personal data can change this person\'s data'),
            };
        }
    }
}

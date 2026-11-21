<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Event\AttachedToHolding;
use Prospecter\Core\Prospecting\Domain\Event\ContactAdded;
use Prospecter\Core\Prospecting\Domain\Event\ContactDetailsChanged;
use Prospecter\Core\Prospecting\Domain\Event\ContactRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ContactsLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\DetachedFromHolding;
use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspected;
use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspicionDismissed;
use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspicionLifted;
use Prospecter\Core\Prospecting\Domain\Event\LegalIdentityRevised;
use Prospecter\Core\Prospecting\Domain\Event\LocatedAgain;
use Prospecter\Core\Prospecting\Domain\Event\LogoChanged;
use Prospecter\Core\Prospecting\Domain\Event\LogoRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ObservationsWritten;
use Prospecter\Core\Prospecting\Domain\Event\OrganizationCompleted;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStarted;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\Prospecting\Domain\Event\Relocated;
use Prospecter\Core\Prospecting\Domain\Exception\AlreadyAContact;
use Prospecter\Core\Prospecting\Domain\Exception\NoContactToRemove;
use Prospecter\Core\Prospecting\Domain\Exception\NoHoldingToDetachFrom;
use Prospecter\Core\Prospecting\Domain\Exception\NoLogoToRemove;
use Prospecter\Core\Prospecting\Domain\Exception\NotAContact;
use Prospecter\Core\Prospecting\Domain\Exception\NotASuspectedDuplicate;
use Prospecter\Core\Prospecting\Domain\Exception\OrganizationWithoutNameNorEmail;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Policy\OrganizationPossibilities;
use Prospecter\Core\Prospecting\Domain\Service\OrphanContacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\Completion;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Location;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Siret;

/**
 * Aggregate root: an organization an account prospects; the whole team of the account shares it.
 */
final class Organization implements ReleasesEvents
{
    use RecordsEvents;

    private const string SIRET = 'siret';
    private const string LINKS = 'links';
    private const string WEBSITE = 'website';
    private const string EMAIL = 'email';
    private const string PHONE_NUMBER = 'phoneNumber';

    private ?OrganizationId $holdingId = null;
    private ?Address $address = null;
    private ?GeoPoint $geoPoint = null;
    private ?string $municipalityCode = null;
    private ?string $observations = null;
    private ?File $logo = null;
    private ExternalReferences $references;
    private ?Website $website = null;
    /** @var list<PersonId> */
    private array $contacts = [];
    /** @var list<OrganizationId> */
    private array $possibleDuplicates = [];
    /** @var list<OrganizationId> */
    private array $notDuplicates = [];
    private Origin $origin = Origin::Hand;
    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly OrganizationId $id,
        private readonly AccountId $accountId,
        private readonly MemberId $createdBy,
        private LegalIdentity $legalIdentity,
        private ContactDetails $contactDetails,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->references = ExternalReferences::none();
        $this->modifiedAt = $createdAt;
    }

    /**
     * By the hand of the prospector, or by an AI acting as them.
     */
    public static function prospectedBy(Prospector $prospector, OrganizationId $id, LegalIdentity $legalIdentity, ContactDetails $contactDetails, \DateTimeImmutable $at, Origin $origin = Origin::Hand): self
    {
        self::ensureItCanBeNamed($legalIdentity, $contactDetails);

        $organization = new self($id, $prospector->accountId, $prospector->id, $legalIdentity, $contactDetails, $at);
        $organization->origin = $origin;
        $organization->recordThat(new ProspectingStarted($prospector->accountId, $prospector->id, $id, $at));

        return $organization;
    }

    public function id(): OrganizationId
    {
        return $this->id;
    }

    /**
     * The account whose prospecting base it belongs to.
     */
    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function contactDetails(): ContactDetails
    {
        return $this->contactDetails;
    }

    /**
     * Its links to the tools outside, each designating it alone in its account.
     */
    public function references(): ExternalReferences
    {
        return $this->references;
    }

    public function website(): ?Website
    {
        return $this->website;
    }

    public function siret(): ?Siret
    {
        return $this->legalIdentity->siret;
    }

    /**
     * How a member names it: its corporate name and its legal form.
     */
    public function fullName(): string
    {
        return $this->legalIdentity->fullName();
    }

    /**
     * An organization is found by the beginning of its corporate name or of its email.
     */
    public function searchKeys(): SearchKeys
    {
        return new SearchKeys($this->legalIdentity->corporateName, $this->contactDetails->email);
    }

    /**
     * What it shares with an organization resembling it, none without a name nor a city.
     */
    public function resemblanceKey(): ?ResemblanceKey
    {
        return ResemblanceKey::of($this->legalIdentity->corporateName, $this->address?->city);
    }

    /**
     * Suspected of being a duplicate of each organization resembling it, and no longer of the others. No member
     * modified it: it keeps the moment it was last modified.
     *
     * @param list<OrganizationId> $resembling
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
     * No longer suspected of being a duplicate of this organization; never suspected, nothing changes.
     */
    public function liftSuspicionOf(OrganizationId $duplicate, MemberId $by, \DateTimeImmutable $at): void
    {
        if (!$this->isSuspectedDuplicateOf($duplicate)) {
            return;
        }

        $this->possibleDuplicates = $this->withoutSuspicionOf($duplicate);
        $this->recordThat(new DuplicateSuspicionLifted($this->accountId, $by, $this->id, $duplicate, $at));
    }

    /**
     * Suspected of being a duplicate of this organization, once, unless a member checked it is not.
     */
    public function suspectDuplicateOf(OrganizationId $duplicate, MemberId $by, \DateTimeImmutable $at): void
    {
        if ($this->isSuspectedDuplicateOf($duplicate) || $this->isNotADuplicateOf($duplicate)) {
            return;
        }

        $this->possibleDuplicates[] = $duplicate;
        $this->recordThat(new DuplicateSuspected($this->accountId, $by, $this->id, $duplicate, $at));
    }

    /**
     * Checked by a member, it is no duplicate of the organization it is suspected of being.
     *
     * @throws NotASuspectedDuplicate
     */
    public function dismissSuspicionOf(OrganizationId $duplicate, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::DismissPossibleDuplicate);

        if (!$this->isSuspectedDuplicateOf($duplicate)) {
            throw $this->notASuspectedDuplicate();
        }

        $this->knowItIsNoDuplicateOf($duplicate, $by, $at);
    }

    private function notASuspectedDuplicate(): NotASuspectedDuplicate
    {
        return NotASuspectedDuplicate::because('The organization is not suspected of being a duplicate of this one', 'duplicateId');
    }

    /**
     * Checked by a member, it is no duplicate of this organization, suspected or not; known already, nothing changes.
     */
    public function knowItIsNoDuplicateOf(OrganizationId $duplicate, MemberId $by, \DateTimeImmutable $at): void
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
     * @return list<OrganizationId>
     */
    private function withoutSuspicionOf(OrganizationId $duplicate): array
    {
        return array_values(array_filter($this->possibleDuplicates, static fn (OrganizationId $suspected): bool => !$suspected->equals($duplicate)));
    }

    private function isNotADuplicateOf(OrganizationId $organization): bool
    {
        return array_any($this->notDuplicates, static fn (OrganizationId $checked): bool => $checked->equals($organization));
    }

    private function isSuspectedDuplicateOf(OrganizationId $duplicate): bool
    {
        return array_any($this->possibleDuplicates, static fn (OrganizationId $suspected): bool => $suspected->equals($duplicate));
    }

    public function isProspectedBy(AccountId $accountId): bool
    {
        return $this->accountId->equals($accountId);
    }

    public function reviseLegalIdentity(LegalIdentity $legalIdentity, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::ReviseLegalIdentity);
        self::ensureItCanBeNamed($legalIdentity, $this->contactDetails);
        $this->legalIdentity = $legalIdentity;
        $this->modifiedAt = $at;
        $this->recordThat(new LegalIdentityRevised($this->accountId, $by, $this->id, $at));
    }

    public function changeContactDetails(ContactDetails $contactDetails, ExternalReferences $references, ?Website $website, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::ChangeContactDetails);
        self::ensureItCanBeNamed($this->legalIdentity, $contactDetails);
        $this->contactDetails = $contactDetails;
        $this->references = $references;
        $this->website = $website;
        $this->modifiedAt = $at;
        $this->recordThat(new ContactDetailsChanged($this->accountId, $by, $this->id, $at));
    }

    /**
     * Fills what the organization lacks, never changing what is noted (ADR 0177); nothing to fill, nothing happens.
     */
    public function completeWith(OrganizationSupplement $supplement, MemberId $by, \DateTimeImmutable $at): void
    {
        $completion = $this->completionWith($supplement);

        if (!$completion->completesAnything()) {
            return;
        }

        $identity = $this->legalIdentity;
        $this->legalIdentity = new LegalIdentity($identity->corporateName, $identity->form, $identity->type, $completion->completes(self::SIRET) ? $supplement->siret : $identity->siret);
        $this->references = ExternalReferences::of([...$this->references->all(), ...$this->references->roomFor($supplement->links)]);
        $this->website = $completion->completes(self::WEBSITE) ? $supplement->website : $this->website;
        $this->contactDetails = new ContactDetails(
            $completion->completes(self::EMAIL) ? $supplement->email : $this->contactDetails->email,
            $completion->completes(self::PHONE_NUMBER) ? $supplement->phoneNumber : $this->contactDetails->phoneNumber,
            $this->contactDetails->language,
        );
        $this->modifiedAt = $at;
        $this->recordThat(new OrganizationCompleted($this->accountId, $by, $this->id, $at));
    }

    /**
     * What the supplement would fill of the organization, and what it gives otherwise than noted.
     */
    public function completionWith(OrganizationSupplement $supplement): Completion
    {
        $this->ensurePossible(OrganizationIntent::Complete);
        $linksNotHeld = $this->references->notHeldAmong($supplement->links);
        $linksToAdd = $this->references->roomFor($supplement->links);

        return new Completion()
            ->with(self::SIRET, $this->legalIdentity->siret?->value, $supplement->siret?->value)
            ->withSome(self::LINKS, filled: count($linksToAdd), left: count($linksNotHeld) - count($linksToAdd))
            ->with(self::WEBSITE, $this->website?->value, $supplement->website?->value)
            ->with(self::EMAIL, $this->contactDetails->email?->value, $supplement->email?->value)
            ->with(self::PHONE_NUMBER, $this->contactDetails->phoneNumber?->value, $supplement->phoneNumber?->value);
    }

    public function relocateTo(?Address $address, ?Location $location, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::Relocate);
        $this->address = null === $address || $address->isEmpty() ? null : $address;
        $this->locateAt($location);
        $this->modifiedAt = $at;
        $this->recordThat(new Relocated($this->accountId, $by, $this->id, $at));
    }

    /**
     * Located before its municipality was kept (ADR 0142): its address geocoded again gives it, once. No member moved
     * it: the event names the one who started prospecting it, and it keeps the moment it was last modified.
     */
    public function locateAgain(?Location $location, \DateTimeImmutable $at): void
    {
        if (!$this->awaitsItsMunicipality() || null === $location) {
            return;
        }

        $this->locateAt($location);
        $this->recordThat(new LocatedAgain($this->accountId, $this->createdBy, $this->id, $at));
    }

    /**
     * The address to geocode again, while it is located without its municipality; null once it has it.
     */
    public function addressToLocateAgain(): ?Address
    {
        return $this->awaitsItsMunicipality() ? $this->address : null;
    }

    private function awaitsItsMunicipality(): bool
    {
        return null !== $this->geoPoint && null === $this->municipalityCode;
    }

    private function locateAt(?Location $location): void
    {
        $this->geoPoint = $location?->point;
        $this->municipalityCode = $location?->municipalityCode;
    }

    public function writeObservations(?string $observations, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::WriteObservations);
        $this->observations = $observations;
        $this->modifiedAt = $at;
        $this->recordThat(new ObservationsWritten($this->accountId, $by, $this->id, $at));
    }

    public function attachToHolding(self $holding, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::AttachToHolding);
        $this->ensureOfTheSameAccount($holding);
        $this->holdingId = $holding->id();
        $this->modifiedAt = $at;
        $this->recordThat(new AttachedToHolding($this->accountId, $by, $this->id, $at));
    }

    public function detachFromHolding(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::DetachFromHolding);
        $this->holdingId = null;
        $this->modifiedAt = $at;
        $this->recordThat(new DetachedFromHolding($this->accountId, $by, $this->id, $at));
    }

    public function changeLogo(File $logo, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::ChangeLogo);
        $this->logo = $logo;
        $this->modifiedAt = $at;
        $this->recordThat(new LogoChanged($this->accountId, $by, $this->id, $at));
    }

    public function removeLogo(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::RemoveLogo);
        $removed = $this->logo;
        $this->logo = null;
        $this->modifiedAt = $at;
        $this->recordThat(new LogoRemoved($this->accountId, $by, $this->id, $removed->url, $at));
    }

    /**
     * @throws AlreadyAContact
     */
    public function addContact(PersonId $contact, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::AddContact);

        if ($this->hasContact($contact)) {
            throw AlreadyAContact::because('This person is a contact of the organization already', 'personId');
        }

        $this->contacts[] = $contact;
        $this->modifiedAt = $at;
        $this->recordThat(new ContactAdded($this->accountId, $by, $this->id, $contact, $at));
    }

    /**
     * A contact no other organization names is left alone: it must not stay (ContactsLeftWithoutOrganization).
     *
     * @throws NotAContact
     */
    public function removeContact(PersonId $contact, MemberId $by, \DateTimeImmutable $at, OrphanContacts $orphans): void
    {
        $this->ensurePossible(OrganizationIntent::RemoveContact);

        if (!$this->hasContact($contact)) {
            throw $this->notAContact();
        }

        $this->contacts = array_values(array_filter($this->contacts, static fn (PersonId $attached): bool => !$attached->equals($contact)));
        $this->modifiedAt = $at;
        $this->recordThat(new ContactRemoved($this->accountId, $by, $this->id, $contact, $at));
        $this->leaveAlone($orphans->among([$contact], $this->accountId, $this->id), $by, $at);
    }

    /**
     * Leaves the prospecting base: once the repository removed it, its contacts no other organization names
     * are left alone (ContactsLeftWithoutOrganization).
     */
    public function stopBeingProspected(MemberId $by, \DateTimeImmutable $at, OrphanContacts $orphans): void
    {
        $this->ensurePossible(OrganizationIntent::StopProspecting);
        $this->recordThat(new ProspectingStopped($this->accountId, $by, $this->id, $at));

        foreach ($this->possibleDuplicates as $suspected) {
            $this->liftSuspicionOf($suspected, $by, $at);
        }

        $this->leaveAlone($orphans->among($this->contacts, $this->accountId, $this->id), $by, $at);
    }

    /**
     * @param list<PersonId> $contacts
     */
    private function leaveAlone(array $contacts, MemberId $by, \DateTimeImmutable $at): void
    {
        if ([] !== $contacts) {
            $this->recordThat(new ContactsLeftWithoutOrganization($this->accountId, $by, $this->id, $contacts, $at));
        }
    }

    /**
     * @throws NotAContact
     */
    public function ensureHasContact(PersonId $contact): void
    {
        if (!$this->hasContact($contact)) {
            throw $this->notAContact();
        }
    }

    public function hasContact(PersonId $contact): bool
    {
        return array_any($this->contacts, static fn (PersonId $attached): bool => $attached->equals($contact));
    }

    private function notAContact(): NotAContact
    {
        return NotAContact::because('This person is no contact of the organization', 'personId');
    }

    private function allowsInItsState(OrganizationIntent $intent): bool
    {
        return in_array($intent, OrganizationPossibilities::given(hasLogo: null !== $this->logo, hasHolding: null !== $this->holdingId, hasContacts: [] !== $this->contacts, hasPossibleDuplicates: [] !== $this->possibleDuplicates), true);
    }

    /**
     * A holding of another account is unknown to it.
     *
     * @throws UnknownOrganization
     */
    private function ensureOfTheSameAccount(self $holding): void
    {
        if (!$holding->isProspectedBy($this->accountId)) {
            throw UnknownOrganization::identifiedBy($holding->id);
        }
    }

    private function ensurePossible(OrganizationIntent $intent): void
    {
        if (!$this->allowsInItsState($intent)) {
            throw $this->notPossibleInItsState($intent);
        }
    }

    private function notPossibleInItsState(OrganizationIntent $intent): NotPossibleInItsState
    {
        return match ($intent) {
            OrganizationIntent::RemoveLogo => NoLogoToRemove::because('There is no logo to remove', 'logo'),
            OrganizationIntent::DetachFromHolding => NoHoldingToDetachFrom::because('There is no holding to detach from', 'holdingId'),
            OrganizationIntent::RemoveContact => NoContactToRemove::because('There is no contact to remove', 'personId'),
            OrganizationIntent::DismissPossibleDuplicate => $this->notASuspectedDuplicate(),
            default => NotPossibleInItsState::because(sprintf('%s is not possible now', $intent->name)),
        };
    }

    private static function ensureItCanBeNamed(LegalIdentity $legalIdentity, ContactDetails $contactDetails): void
    {
        if (!$legalIdentity->hasCorporateName() && null === $contactDetails->email) {
            throw OrganizationWithoutNameNorEmail::because('At least one is mandatory : corporate name or email');
        }
    }
}

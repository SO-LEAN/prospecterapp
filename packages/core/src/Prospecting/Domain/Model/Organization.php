<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Event\AttachedToHolding;
use Prospecter\Core\Prospecting\Domain\Event\ContactAdded;
use Prospecter\Core\Prospecting\Domain\Event\ContactDetailsChanged;
use Prospecter\Core\Prospecting\Domain\Event\ContactRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ContactsLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\DetachedFromHolding;
use Prospecter\Core\Prospecting\Domain\Event\LegalIdentityRevised;
use Prospecter\Core\Prospecting\Domain\Event\LogoChanged;
use Prospecter\Core\Prospecting\Domain\Event\LogoRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ObservationsWritten;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStarted;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\Prospecting\Domain\Event\Relocated;
use Prospecter\Core\Prospecting\Domain\Exception\AlreadyAContact;
use Prospecter\Core\Prospecting\Domain\Exception\NoContactToRemove;
use Prospecter\Core\Prospecting\Domain\Exception\NoHoldingToDetachFrom;
use Prospecter\Core\Prospecting\Domain\Exception\NoLogoToRemove;
use Prospecter\Core\Prospecting\Domain\Exception\NotAContact;
use Prospecter\Core\Prospecting\Domain\Exception\OrganizationWithoutNameNorEmail;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Policy\OrganizationPossibilities;
use Prospecter\Core\Prospecting\Domain\Service\OrphanContacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;

/**
 * Aggregate root: an organization an account prospects; the whole team of the account shares it.
 */
final class Organization implements ReleasesEvents
{
    use RecordsEvents;

    private ?OrganizationId $holdingId = null;
    private ?Address $address = null;
    private ?GeoPoint $geoPoint = null;
    private ?string $observations = null;
    private ?File $logo = null;
    /** @var list<PersonId> */
    private array $contacts = [];
    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly OrganizationId $id,
        private readonly AccountId $accountId,
        private readonly MemberId $createdBy,
        private LegalIdentity $legalIdentity,
        private ContactDetails $contactDetails,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    public static function prospectedBy(Prospector $prospector, OrganizationId $id, LegalIdentity $legalIdentity, ContactDetails $contactDetails, \DateTimeImmutable $at): self
    {
        self::ensureItCanBeNamed($legalIdentity, $contactDetails);

        $organization = new self($id, $prospector->accountId, $prospector->id, $legalIdentity, $contactDetails, $at);
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

    /**
     * Who entered it, for the record: every prospector of the account manages it alike.
     */
    public function createdBy(): MemberId
    {
        return $this->createdBy;
    }

    public function holdingId(): ?OrganizationId
    {
        return $this->holdingId;
    }

    public function legalIdentity(): LegalIdentity
    {
        return $this->legalIdentity;
    }

    public function fullName(): string
    {
        return $this->legalIdentity->fullName();
    }

    public function contactDetails(): ContactDetails
    {
        return $this->contactDetails;
    }

    /**
     * An organization is found by the beginning of its corporate name or of its email.
     */
    public function searchKeys(): SearchKeys
    {
        return new SearchKeys($this->legalIdentity->corporateName, $this->contactDetails->email);
    }

    public function address(): ?Address
    {
        return $this->address;
    }

    public function geoPoint(): ?GeoPoint
    {
        return $this->geoPoint;
    }

    public function observations(): ?string
    {
        return $this->observations;
    }

    public function logo(): ?File
    {
        return $this->logo;
    }

    /**
     * The persons prospected in the organization, kept by PersonalData (ADR 0041).
     *
     * @return list<PersonId>
     */
    public function contacts(): array
    {
        return $this->contacts;
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

    public function changeContactDetails(ContactDetails $contactDetails, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::ChangeContactDetails);
        self::ensureItCanBeNamed($this->legalIdentity, $contactDetails);
        $this->contactDetails = $contactDetails;
        $this->modifiedAt = $at;
        $this->recordThat(new ContactDetailsChanged($this->accountId, $by, $this->id, $at));
    }

    public function relocateTo(?Address $address, ?GeoPoint $geoPoint, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::Relocate);
        $this->address = null === $address || $address->isEmpty() ? null : $address;
        $this->geoPoint = $geoPoint;
        $this->modifiedAt = $at;
        $this->recordThat(new Relocated($this->accountId, $by, $this->id, $at));
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

    public function removeLogo(MemberId $by, \DateTimeImmutable $at): File
    {
        $this->ensurePossible(OrganizationIntent::RemoveLogo);
        $removed = $this->logo;
        $this->logo = null;
        $this->modifiedAt = $at;
        $this->recordThat(new LogoRemoved($this->accountId, $by, $this->id, $at));

        return $removed;
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
        return in_array($intent, OrganizationPossibilities::given(hasLogo: null !== $this->logo, hasHolding: null !== $this->holdingId, hasContacts: [] !== $this->contacts), true);
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

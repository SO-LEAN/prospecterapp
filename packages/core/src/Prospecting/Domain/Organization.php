<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\Prospecting\Domain\Event\AttachedToHolding;
use Prospecter\Core\Prospecting\Domain\Event\ContactAdded;
use Prospecter\Core\Prospecting\Domain\Event\ContactDetailsChanged;
use Prospecter\Core\Prospecting\Domain\Event\ContactRemoved;
use Prospecter\Core\Prospecting\Domain\Event\DetachedFromHolding;
use Prospecter\Core\Prospecting\Domain\Event\LegalIdentityRevised;
use Prospecter\Core\Prospecting\Domain\Event\LogoChanged;
use Prospecter\Core\Prospecting\Domain\Event\LogoRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ObservationsWritten;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStarted;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\Prospecting\Domain\Event\Relocated;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Event\ContactsLeftWithoutOrganization;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: an organization an account prospects; the whole team of the account shares it.
 */
class Organization implements ReleasesEvents
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

    /**
     * What this prospector may do with the organization now: nothing if their account does not prospect
     * it, and only what its state allows. Every intention refuses exactly what is missing here.
     *
     * @return list<OrganizationIntent>
     */
    public function possibleIntentsOf(Prospector $prospector): array
    {
        if (!$this->isProspectedBy($prospector->accountId)) {
            return [];
        }

        return array_values(array_filter(OrganizationIntent::cases(), fn (OrganizationIntent $intent): bool => $this->isAllowedTo($prospector, $intent) && $this->allowsInItsState($intent)));
    }

    /**
     * Another account does not even learn that the organization exists (ADR 0019).
     */
    public function ensureReachableBy(Prospector $prospector): void
    {
        if (!$this->isProspectedBy($prospector->accountId)) {
            throw UnknownOrganization::identifiedBy($this->id);
        }
    }

    public function reviseLegalIdentity(LegalIdentity $legalIdentity, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::ReviseLegalIdentity, $by);
        self::ensureItCanBeNamed($legalIdentity, $this->contactDetails);
        $this->legalIdentity = $legalIdentity;
        $this->modifiedAt = $at;
        $this->recordThat(new LegalIdentityRevised($this->accountId, $by->id, $this->id, $at));
    }

    public function changeContactDetails(ContactDetails $contactDetails, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::ChangeContactDetails, $by);
        self::ensureItCanBeNamed($this->legalIdentity, $contactDetails);
        $this->contactDetails = $contactDetails;
        $this->modifiedAt = $at;
        $this->recordThat(new ContactDetailsChanged($this->accountId, $by->id, $this->id, $at));
    }

    public function relocateTo(?Address $address, ?GeoPoint $geoPoint, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::Relocate, $by);
        $this->address = null === $address || $address->isEmpty() ? null : $address;
        $this->geoPoint = $geoPoint;
        $this->modifiedAt = $at;
        $this->recordThat(new Relocated($this->accountId, $by->id, $this->id, $at));
    }

    public function writeObservations(?string $observations, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::WriteObservations, $by);
        $this->observations = $observations;
        $this->modifiedAt = $at;
        $this->recordThat(new ObservationsWritten($this->accountId, $by->id, $this->id, $at));
    }

    public function attachToHolding(self $holding, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::AttachToHolding, $by);
        $holding->ensureReachableBy($by);
        $this->holdingId = $holding->id();
        $this->modifiedAt = $at;
        $this->recordThat(new AttachedToHolding($this->accountId, $by->id, $this->id, $at));
    }

    public function detachFromHolding(Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::DetachFromHolding, $by);
        $this->holdingId = null;
        $this->modifiedAt = $at;
        $this->recordThat(new DetachedFromHolding($this->accountId, $by->id, $this->id, $at));
    }

    public function changeLogo(File $logo, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::ChangeLogo, $by);
        $this->logo = $logo;
        $this->modifiedAt = $at;
        $this->recordThat(new LogoChanged($this->accountId, $by->id, $this->id, $at));
    }

    public function removeLogo(Prospector $by, \DateTimeImmutable $at): File
    {
        $this->ensurePossible(OrganizationIntent::RemoveLogo, $by);
        $removed = $this->logo;
        $this->logo = null;
        $this->modifiedAt = $at;
        $this->recordThat(new LogoRemoved($this->accountId, $by->id, $this->id, $at));

        return $removed;
    }

    /**
     * @throws NotPossibleInItsState when the person is a contact already
     */
    public function addContact(PersonId $contact, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(OrganizationIntent::AddContact, $by);

        if ($this->hasContact($contact)) {
            throw NotPossibleInItsState::because('This person is a contact of the organization already', 'personId');
        }

        $this->contacts[] = $contact;
        $this->modifiedAt = $at;
        $this->recordThat(new ContactAdded($this->accountId, $by->id, $this->id, $contact, $at));
    }

    /**
     * A contact no other organization names is left alone: it must not stay (ContactsLeftWithoutOrganization).
     *
     * @throws NotPossibleInItsState when the person is no contact of the organization
     */
    public function removeContact(PersonId $contact, Prospector $by, \DateTimeImmutable $at, OrphanContacts $orphans): void
    {
        $this->ensurePossible(OrganizationIntent::RemoveContact, $by);

        if (!$this->hasContact($contact)) {
            throw NotPossibleInItsState::because('This person is no contact of the organization', 'personId');
        }

        $this->contacts = array_values(array_filter($this->contacts, static fn (PersonId $attached): bool => !$attached->equals($contact)));
        $this->modifiedAt = $at;
        $this->recordThat(new ContactRemoved($this->accountId, $by->id, $this->id, $contact, $at));
        $this->leaveAlone($orphans->among([$contact], $this->accountId, $this->id), $by, $at);
    }

    /**
     * Leaves the prospecting base: once the repository removed it, its contacts no other organization names
     * are left alone (ContactsLeftWithoutOrganization).
     *
     * @throws UnknownOrganization|NotAllowed
     */
    public function stopBeingProspected(Prospector $by, \DateTimeImmutable $at, OrphanContacts $orphans): void
    {
        $this->ensurePossible(OrganizationIntent::StopProspecting, $by);
        $this->recordThat(new ProspectingStopped($this->accountId, $by->id, $this->id, $at));
        $this->leaveAlone($orphans->among($this->contacts, $this->accountId, $this->id), $by, $at);
    }

    /**
     * @param list<PersonId> $contacts
     */
    private function leaveAlone(array $contacts, Prospector $by, \DateTimeImmutable $at): void
    {
        if ([] !== $contacts) {
            $this->recordThat(new ContactsLeftWithoutOrganization($this->accountId, $by->id, $this->id, $contacts, $at));
        }
    }

    public function hasContact(PersonId $contact): bool
    {
        return array_any($this->contacts, static fn (PersonId $attached): bool => $attached->equals($contact));
    }

    private function allowsInItsState(OrganizationIntent $intent): bool
    {
        return match ($intent) {
            OrganizationIntent::RemoveLogo => null !== $this->logo,
            OrganizationIntent::DetachFromHolding => null !== $this->holdingId,
            OrganizationIntent::RemoveContact => [] !== $this->contacts,
            default => true,
        };
    }

    /**
     * The contacts are personal data: managing them, or stopping to prospect an organization that has some
     * (they may be erased with it), needs the right to manage personal data.
     */
    private function isAllowedTo(Prospector $prospector, OrganizationIntent $intent): bool
    {
        return $prospector->managesPersonalData || !$this->touchesContacts($intent);
    }

    private function touchesContacts(OrganizationIntent $intent): bool
    {
        return match ($intent) {
            OrganizationIntent::AddContact, OrganizationIntent::RemoveContact => true,
            OrganizationIntent::StopProspecting => [] !== $this->contacts,
            default => false,
        };
    }

    private function ensurePossible(OrganizationIntent $intent, Prospector $by): void
    {
        $this->ensureReachableBy($by);

        if (!$this->isAllowedTo($by, $intent)) {
            throw NotAllowed::because('Only a prospector allowed to manage personal data may manage the contacts');
        }

        if (!$this->allowsInItsState($intent)) {
            throw $this->notPossibleInItsState($intent);
        }
    }

    private function notPossibleInItsState(OrganizationIntent $intent): NotPossibleInItsState
    {
        return match ($intent) {
            OrganizationIntent::RemoveLogo => NotPossibleInItsState::because('There is no logo to remove', 'logo'),
            OrganizationIntent::DetachFromHolding => NotPossibleInItsState::because('There is no holding to detach from', 'holdingId'),
            OrganizationIntent::RemoveContact => NotPossibleInItsState::because('There is no contact to remove', 'personId'),
            default => NotPossibleInItsState::because(sprintf('%s is not possible now', $intent->name)),
        };
    }

    private static function ensureItCanBeNamed(LegalIdentity $legalIdentity, ContactDetails $contactDetails): void
    {
        if (!$legalIdentity->hasCorporateName() && null === $contactDetails->email) {
            throw BusinessRuleViolation::because('At least one is mandatory : corporate name or email');
        }
    }
}

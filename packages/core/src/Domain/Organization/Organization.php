<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Organization;

use Prospecter\Core\Domain\Shared\BusinessRuleViolation;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\File;
use Prospecter\Core\Domain\Shared\NotPossibleInItsState;
use Prospecter\Core\Domain\User\User;

/**
 * Aggregate root. Either an organization using Prospecter, or one it prospects (it has an owner).
 */
class Organization
{
    private ?OrganizationId $ownerId = null;
    private ?OrganizationId $holdingId = null;
    private ContactDetails $contactDetails;
    private ?Address $address = null;
    private ?GeoPoint $geoPoint = null;
    private ?string $observations = null;
    private ?File $logo = null;

    private function __construct(private readonly OrganizationId $id, private LegalIdentity $legalIdentity)
    {
        $this->contactDetails = new ContactDetails();
    }

    public static function usingProspecter(OrganizationId $id, LegalIdentity $legalIdentity): self
    {
        return new self($id, $legalIdentity);
    }

    public static function prospectedBy(User $prospector, OrganizationId $id, LegalIdentity $legalIdentity, ContactDetails $contactDetails): self
    {
        self::ensureItCanBeNamed($legalIdentity, $contactDetails);
        $organization = new self($id, $legalIdentity);
        $organization->ownerId = $prospector->organizationId();
        $organization->contactDetails = $contactDetails;

        return $organization;
    }

    public function id(): OrganizationId
    {
        return $this->id;
    }

    public function ownerId(): ?OrganizationId
    {
        return $this->ownerId;
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

    public function isProspectedBy(OrganizationId $organizationId): bool
    {
        return null !== $this->ownerId && $this->ownerId->equals($organizationId);
    }

    /**
     * What this user may do with the organization now: nothing if it is not theirs, and only what its
     * state allows. Every intention refuses exactly what is missing here.
     *
     * @return list<OrganizationIntent>
     */
    public function possibleIntentsOf(User $user): array
    {
        if (!$this->isManagedBy($user)) {
            return [];
        }

        return array_values(array_filter(OrganizationIntent::cases(), $this->allowsInItsState(...)));
    }

    public function ensureReachableBy(User $user): void
    {
        if (!$this->isManagedBy($user)) {
            throw new UnknownOrganization($this->id());
        }
    }

    public function reviseLegalIdentity(LegalIdentity $legalIdentity, User $by): void
    {
        $this->ensurePossible(OrganizationIntent::ReviseLegalIdentity, $by);
        $this->ensureAProspectCanBeNamed($legalIdentity, $this->contactDetails);
        $this->legalIdentity = $legalIdentity;
    }

    public function changeContactDetails(ContactDetails $contactDetails, User $by): void
    {
        $this->ensurePossible(OrganizationIntent::ChangeContactDetails, $by);
        $this->ensureAProspectCanBeNamed($this->legalIdentity, $contactDetails);
        $this->contactDetails = $contactDetails;
    }

    public function relocateTo(?Address $address, ?GeoPoint $geoPoint, User $by): void
    {
        $this->ensurePossible(OrganizationIntent::Relocate, $by);
        $this->address = null === $address || $address->isEmpty() ? null : $address;
        $this->geoPoint = $geoPoint;
    }

    public function writeObservations(?string $observations, User $by): void
    {
        $this->ensurePossible(OrganizationIntent::WriteObservations, $by);
        $this->observations = $observations;
    }

    public function attachToHolding(self $holding, User $by): void
    {
        $this->ensurePossible(OrganizationIntent::AttachToHolding, $by);
        $holding->ensureProspectedBy($by);
        $this->holdingId = $holding->id();
    }

    public function detachFromHolding(User $by): void
    {
        $this->ensurePossible(OrganizationIntent::DetachFromHolding, $by);
        $this->holdingId = null;
    }

    public function changeLogo(File $logo, User $by): void
    {
        $this->ensurePossible(OrganizationIntent::ChangeLogo, $by);
        $this->logo = $logo;
    }

    public function removeLogo(User $by): File
    {
        $this->ensurePossible(OrganizationIntent::RemoveLogo, $by);
        $removed = $this->logo;
        $this->logo = null;

        return $removed;
    }

    /**
     * Its own users manage the organization using Prospecter; the users of the organization prospecting
     * it manage a prospect. Anybody else does not even learn that it exists (ADR 0019).
     */
    private function isManagedBy(User $user): bool
    {
        return $this->id->equals($user->organizationId()) || $this->isProspectedBy($user->organizationId());
    }

    private function allowsInItsState(OrganizationIntent $intent): bool
    {
        return match ($intent) {
            OrganizationIntent::RemoveLogo => null !== $this->logo,
            OrganizationIntent::DetachFromHolding => null !== $this->holdingId,
            default => true,
        };
    }

    private function ensurePossible(OrganizationIntent $intent, User $by): void
    {
        $this->ensureReachableBy($by);

        if (!$this->allowsInItsState($intent)) {
            throw $this->notPossibleInItsState($intent);
        }
    }

    private function ensureProspectedBy(User $prospector): void
    {
        if (!$this->isProspectedBy($prospector->organizationId())) {
            throw new UnknownOrganization($this->id());
        }
    }

    private function notPossibleInItsState(OrganizationIntent $intent): NotPossibleInItsState
    {
        return match ($intent) {
            OrganizationIntent::RemoveLogo => new NotPossibleInItsState('There is no logo to remove', 'logo'),
            OrganizationIntent::DetachFromHolding => new NotPossibleInItsState('There is no holding to detach from', 'holdingId'),
            default => new NotPossibleInItsState(sprintf('%s is not possible now', $intent->name)),
        };
    }

    private function ensureAProspectCanBeNamed(LegalIdentity $legalIdentity, ContactDetails $contactDetails): void
    {
        if (null !== $this->ownerId) {
            self::ensureItCanBeNamed($legalIdentity, $contactDetails);
        }
    }

    private static function ensureItCanBeNamed(LegalIdentity $legalIdentity, ContactDetails $contactDetails): void
    {
        if (!$legalIdentity->hasCorporateName() && null === $contactDetails->email) {
            throw new BusinessRuleViolation('At least one is mandatory : corporate name or email');
        }
    }
}

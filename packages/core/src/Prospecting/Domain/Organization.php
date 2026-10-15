<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\NotPossibleInItsState;

/**
 * Aggregate root: an organization an account prospects, its owner.
 */
class Organization
{
    private ?OrganizationId $holdingId = null;
    private ?Address $address = null;
    private ?GeoPoint $geoPoint = null;
    private ?string $observations = null;
    private ?File $logo = null;

    private function __construct(
        private readonly OrganizationId $id,
        private readonly AccountId $ownerId,
        private LegalIdentity $legalIdentity,
        private ContactDetails $contactDetails,
    ) {
    }

    public static function prospectedBy(Prospector $prospector, OrganizationId $id, LegalIdentity $legalIdentity, ContactDetails $contactDetails): self
    {
        self::ensureItCanBeNamed($legalIdentity, $contactDetails);

        return new self($id, $prospector->accountId, $legalIdentity, $contactDetails);
    }

    public function id(): OrganizationId
    {
        return $this->id;
    }

    public function ownerId(): AccountId
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

    public function isProspectedBy(AccountId $accountId): bool
    {
        return $this->ownerId->equals($accountId);
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

        return array_values(array_filter(OrganizationIntent::cases(), $this->allowsInItsState(...)));
    }

    /**
     * Another account does not even learn that the organization exists (ADR 0019).
     */
    public function ensureReachableBy(Prospector $prospector): void
    {
        if (!$this->isProspectedBy($prospector->accountId)) {
            throw new UnknownOrganization($this->id);
        }
    }

    public function reviseLegalIdentity(LegalIdentity $legalIdentity, Prospector $by): void
    {
        $this->ensurePossible(OrganizationIntent::ReviseLegalIdentity, $by);
        self::ensureItCanBeNamed($legalIdentity, $this->contactDetails);
        $this->legalIdentity = $legalIdentity;
    }

    public function changeContactDetails(ContactDetails $contactDetails, Prospector $by): void
    {
        $this->ensurePossible(OrganizationIntent::ChangeContactDetails, $by);
        self::ensureItCanBeNamed($this->legalIdentity, $contactDetails);
        $this->contactDetails = $contactDetails;
    }

    public function relocateTo(?Address $address, ?GeoPoint $geoPoint, Prospector $by): void
    {
        $this->ensurePossible(OrganizationIntent::Relocate, $by);
        $this->address = null === $address || $address->isEmpty() ? null : $address;
        $this->geoPoint = $geoPoint;
    }

    public function writeObservations(?string $observations, Prospector $by): void
    {
        $this->ensurePossible(OrganizationIntent::WriteObservations, $by);
        $this->observations = $observations;
    }

    public function attachToHolding(self $holding, Prospector $by): void
    {
        $this->ensurePossible(OrganizationIntent::AttachToHolding, $by);
        $holding->ensureReachableBy($by);
        $this->holdingId = $holding->id();
    }

    public function detachFromHolding(Prospector $by): void
    {
        $this->ensurePossible(OrganizationIntent::DetachFromHolding, $by);
        $this->holdingId = null;
    }

    public function changeLogo(File $logo, Prospector $by): void
    {
        $this->ensurePossible(OrganizationIntent::ChangeLogo, $by);
        $this->logo = $logo;
    }

    public function removeLogo(Prospector $by): File
    {
        $this->ensurePossible(OrganizationIntent::RemoveLogo, $by);
        $removed = $this->logo;
        $this->logo = null;

        return $removed;
    }

    private function allowsInItsState(OrganizationIntent $intent): bool
    {
        return match ($intent) {
            OrganizationIntent::RemoveLogo => null !== $this->logo,
            OrganizationIntent::DetachFromHolding => null !== $this->holdingId,
            default => true,
        };
    }

    private function ensurePossible(OrganizationIntent $intent, Prospector $by): void
    {
        $this->ensureReachableBy($by);

        if (!$this->allowsInItsState($intent)) {
            throw $this->notPossibleInItsState($intent);
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

    private static function ensureItCanBeNamed(LegalIdentity $legalIdentity, ContactDetails $contactDetails): void
    {
        if (!$legalIdentity->hasCorporateName() && null === $contactDetails->email) {
            throw new BusinessRuleViolation('At least one is mandatory : corporate name or email');
        }
    }
}

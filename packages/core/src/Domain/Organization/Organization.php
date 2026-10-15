<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Organization;

use Prospecter\Core\Domain\Shared\BusinessRuleViolation;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\Email;
use Prospecter\Core\Domain\Shared\File;

/**
 * Aggregate root. Either an organization using Prospecter, or one it prospects (it has an owner).
 * Its state is kept in plain fields, the columns Doctrine maps, and exposed as value objects.
 */
class Organization
{
    private readonly string $id;
    private ?string $ownerId = null;
    private ?string $holdingId = null;
    private ?string $corporateName;
    private ?string $form;
    private ?string $type;
    private ?string $email = null;
    private ?string $phoneNumber = null;
    private ?string $language = null;
    private ?string $addressStreet = null;
    private ?string $addressPostalCode = null;
    private ?string $addressCity = null;
    private ?string $addressCountry = null;
    private ?float $geoPointLongitude = null;
    private ?float $geoPointLatitude = null;
    private ?string $observations = null;
    private ?string $logoUrl = null;
    private ?string $logoExtension = null;
    private ?int $logoSize = null;

    private function __construct(OrganizationId $id, LegalIdentity $legalIdentity)
    {
        $this->id = $id->value;
        $this->assignLegalIdentity($legalIdentity);
    }

    public static function usingProspecter(OrganizationId $id, LegalIdentity $legalIdentity): self
    {
        return new self($id, $legalIdentity);
    }

    public static function prospectedBy(OrganizationId $ownerId, OrganizationId $id, LegalIdentity $legalIdentity, ContactDetails $contactDetails): self
    {
        self::ensureItCanBeNamed($legalIdentity, $contactDetails);
        $organization = new self($id, $legalIdentity);
        $organization->ownerId = $ownerId->value;
        $organization->assignContactDetails($contactDetails);

        return $organization;
    }

    public function id(): OrganizationId
    {
        return new OrganizationId($this->id);
    }

    public function ownerId(): ?OrganizationId
    {
        return null === $this->ownerId ? null : new OrganizationId($this->ownerId);
    }

    public function holdingId(): ?OrganizationId
    {
        return null === $this->holdingId ? null : new OrganizationId($this->holdingId);
    }

    public function legalIdentity(): LegalIdentity
    {
        return new LegalIdentity($this->corporateName, $this->form, $this->type);
    }

    public function fullName(): string
    {
        return $this->legalIdentity()->fullName();
    }

    public function contactDetails(): ContactDetails
    {
        return new ContactDetails(Email::orNone($this->email), $this->phoneNumber, $this->language);
    }

    public function address(): ?Address
    {
        $address = new Address($this->addressStreet, $this->addressPostalCode, $this->addressCity, $this->addressCountry);

        return $address->isEmpty() ? null : $address;
    }

    public function geoPoint(): ?GeoPoint
    {
        if (null === $this->geoPointLongitude || null === $this->geoPointLatitude) {
            return null;
        }

        return new GeoPoint($this->geoPointLongitude, $this->geoPointLatitude);
    }

    public function observations(): ?string
    {
        return $this->observations;
    }

    public function logo(): ?File
    {
        return null === $this->logoUrl ? null : new File($this->logoUrl, (string) $this->logoExtension, (int) $this->logoSize);
    }

    public function isProspectedBy(OrganizationId $organizationId): bool
    {
        return $this->ownerId === $organizationId->value;
    }

    public function isVisibleTo(OrganizationId $organizationId): bool
    {
        return $this->id === $organizationId->value || $this->isProspectedBy($organizationId);
    }

    public function reviseLegalIdentity(LegalIdentity $legalIdentity): void
    {
        $this->ensureAProspectCanBeNamed($legalIdentity, $this->contactDetails());
        $this->assignLegalIdentity($legalIdentity);
    }

    public function changeContactDetails(ContactDetails $contactDetails): void
    {
        $this->ensureAProspectCanBeNamed($this->legalIdentity(), $contactDetails);
        $this->assignContactDetails($contactDetails);
    }

    public function relocateTo(?Address $address, ?GeoPoint $geoPoint): void
    {
        $this->addressStreet = $address?->street;
        $this->addressPostalCode = $address?->postalCode;
        $this->addressCity = $address?->city;
        $this->addressCountry = $address?->country;
        $this->geoPointLongitude = $geoPoint?->longitude;
        $this->geoPointLatitude = $geoPoint?->latitude;
    }

    public function writeObservations(?string $observations): void
    {
        $this->observations = $observations;
    }

    public function attachToHolding(OrganizationId $holdingId): void
    {
        $this->holdingId = $holdingId->value;
    }

    public function detachFromHolding(): void
    {
        $this->holdingId = null;
    }

    public function changeLogo(File $logo): void
    {
        $this->logoUrl = $logo->url;
        $this->logoExtension = $logo->extension;
        $this->logoSize = $logo->size;
    }

    public function removeLogo(): File
    {
        $logo = $this->logo() ?? throw new BusinessRuleViolation('There is no logo to remove', 'logo');
        $this->logoUrl = null;
        $this->logoExtension = null;
        $this->logoSize = null;

        return $logo;
    }

    private function assignLegalIdentity(LegalIdentity $legalIdentity): void
    {
        $this->corporateName = $legalIdentity->corporateName;
        $this->form = $legalIdentity->form;
        $this->type = $legalIdentity->type;
    }

    private function assignContactDetails(ContactDetails $contactDetails): void
    {
        $this->email = $contactDetails->email?->value;
        $this->phoneNumber = $contactDetails->phoneNumber;
        $this->language = $contactDetails->language;
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

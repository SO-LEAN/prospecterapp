<?php

declare(strict_types=1);

namespace Prospecter\Core\Entity;

use Prospecter\Core\Exception\Entity\ValidationException;

class Organization extends Person implements GeoLocatable
{
    private const string ACTIVE_ORGANIZATIONS = 'activeOrganizations';

    private ?string $corporateName = null;
    private ?string $form = null;
    private ?string $type = null;
    private ?string $addressStreet = null;
    private ?string $addressPostalCode = null;
    private ?string $addressCity = null;
    private ?string $addressCountry = null;
    private ?float $geoPointLongitude = null;
    private ?float $geoPointLatitude = null;
    private ?string $logoUrl = null;
    private ?string $logoExtension = null;
    private ?int $logoSize = null;
    private ?Organization $holdBy = null;
    private ?Organization $ownedBy = null;
    /** @var array<string, int> */
    private array $stats = [self::ACTIVE_ORGANIZATIONS => 0];

    public function getCorporateName(): ?string
    {
        return $this->corporateName;
    }

    public function setCorporateName(?string $corporateName): void
    {
        $this->corporateName = $corporateName;
    }

    public function getForm(): ?string
    {
        return $this->form;
    }

    public function setForm(?string $form): void
    {
        $this->form = $form;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->corporateName, $this->form));
    }

    public function getAddress(): ?Address
    {
        $address = new Address($this->addressStreet, $this->addressPostalCode, $this->addressCity, $this->addressCountry);

        return $address->isEmpty() ? null : $address;
    }

    public function setAddress(?Address $address): void
    {
        $this->addressStreet = $address?->street;
        $this->addressPostalCode = $address?->postalCode;
        $this->addressCity = $address?->city;
        $this->addressCountry = $address?->country;
    }

    public function getGeoPoint(): ?GeoPoint
    {
        if (null === $this->geoPointLongitude || null === $this->geoPointLatitude) {
            return null;
        }

        return new GeoPoint($this->geoPointLongitude, $this->geoPointLatitude);
    }

    public function setGeoPoint(?GeoPoint $geoPoint): void
    {
        $this->geoPointLongitude = $geoPoint?->longitude;
        $this->geoPointLatitude = $geoPoint?->latitude;
    }

    public function getLogo(): ?File
    {
        return null === $this->logoUrl ? null : new File($this->logoUrl, (string) $this->logoExtension, (int) $this->logoSize);
    }

    public function setLogo(?File $logo): void
    {
        $this->logoUrl = $logo?->url;
        $this->logoExtension = $logo?->extension;
        $this->logoSize = $logo?->size;
    }

    public function removeLogo(): void
    {
        $this->setLogo(null);
    }

    public function getHoldBy(): ?Organization
    {
        return $this->holdBy;
    }

    public function setHoldBy(?Organization $holdBy): void
    {
        $this->holdBy = $holdBy;
    }

    public function getOwnedBy(): ?Organization
    {
        return $this->ownedBy;
    }

    public function setOwnedBy(Organization $owner): void
    {
        $this->ownedBy = $owner;
        $owner->countOneMoreActiveOrganization();
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    public function getStat(string $name): int
    {
        return $this->stats[$name] ?? 0;
    }

    public function validate(): void
    {
        if ($this->ownedBy instanceof Organization && !$this->hasCorporateNameOrEmail()) {
            throw new ValidationException('At least one is mandatory : corporate name or email', '*');
        }
    }

    private function hasCorporateNameOrEmail(): bool
    {
        return !in_array($this->corporateName, [null, ''], true) || !in_array($this->getEmail(), [null, ''], true);
    }

    private function countOneMoreActiveOrganization(): void
    {
        $this->stats[self::ACTIVE_ORGANIZATIONS] = $this->getStat(self::ACTIVE_ORGANIZATIONS) + 1;
    }
}

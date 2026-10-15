<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\Address;
use Prospecter\Core\Prospecting\Domain\GeoPoint;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationIntent;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;

/**
 * An organization as every use case on organizations presents it, with what the user may do with it now.
 */
final readonly class OrganizationResponse
{
    /**
     * @param list<OrganizationIntent> $possibleIntents
     */
    public function __construct(
        public OrganizationId $id,
        public AccountId $ownerId,
        public LegalIdentity $legalIdentity,
        public string $fullName,
        public ContactDetails $contactDetails,
        public ?Address $address,
        public ?GeoPoint $geoPoint,
        public ?string $observations,
        public ?File $logo,
        public ?OrganizationId $holdingId,
        public array $possibleIntents,
    ) {
    }

    public static function of(Organization $organization, Prospector $viewer): self
    {
        return new self(
            id: $organization->id(),
            ownerId: $organization->ownerId(),
            legalIdentity: $organization->legalIdentity(),
            fullName: $organization->fullName(),
            contactDetails: $organization->contactDetails(),
            address: $organization->address(),
            geoPoint: $organization->geoPoint(),
            observations: $organization->observations(),
            logo: $organization->logo(),
            holdingId: $organization->holdingId(),
            possibleIntents: $organization->possibleIntentsOf($viewer),
        );
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Domain\Organization\Address;
use Prospecter\Core\Domain\Organization\GeoPoint;
use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Organization\OrganizationIntent;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\File;

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
        public ?OrganizationId $ownerId,
        public LegalIdentity $legalIdentity,
        public string $fullName,
        public ContactDetails $contactDetails,
        public ?Address $address,
        public ?GeoPoint $geoPoint,
        public ?string $observations,
        public ?File $logo,
        public ?OrganizationId $holdingId,
        public int $activeOrganizations,
        public array $possibleIntents,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\SearchProspectedOrganizations;

use Prospecter\Core\Domain\Organization\Address;
use Prospecter\Core\Domain\Organization\GeoPoint;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Shared\File;

final readonly class FoundOrganization
{
    public function __construct(
        public OrganizationId $id,
        public string $fullName,
        public ?Address $address,
        public ?File $logo,
        public ?GeoPoint $geoPoint,
    ) {
    }
}

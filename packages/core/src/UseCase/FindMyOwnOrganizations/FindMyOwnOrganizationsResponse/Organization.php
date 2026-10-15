<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse;

use Prospecter\Core\Entity\Address;
use Prospecter\Core\Entity\File;
use Prospecter\Core\Entity\GeoPoint;

final readonly class Organization
{
    public function __construct(
        public int $id,
        public string $fullName,
        public ?Address $address,
        public ?File $logo,
        public ?GeoPoint $geoPoint,
    ) {
    }
}

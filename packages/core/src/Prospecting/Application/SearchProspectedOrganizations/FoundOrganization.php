<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\Prospecting\Domain\Address;
use Prospecter\Core\Prospecting\Domain\GeoPoint;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\File;

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

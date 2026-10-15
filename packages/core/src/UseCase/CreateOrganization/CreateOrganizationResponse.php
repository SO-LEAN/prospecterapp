<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\CreateOrganization;

use Prospecter\Core\Entity\Address;
use Prospecter\Core\Entity\File;
use Prospecter\Core\Entity\GeoPoint;

final readonly class CreateOrganizationResponse
{
    public function __construct(
        public int $id,
        public int $ownedBy,
        public ?string $phoneNumber,
        public ?string $email,
        public ?string $language,
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
        public ?Address $address,
        public ?GeoPoint $geoPoint,
        public ?string $observations,
        public ?File $logo,
        public ?int $holdBy,
    ) {
    }
}

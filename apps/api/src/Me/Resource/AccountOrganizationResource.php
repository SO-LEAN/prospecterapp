<?php

namespace App\Me\Resource;

use App\Shared\Resource\AddressResource;
use App\Shared\Resource\ImageResource;
use App\Shared\Resource\LegalIdentityResource;
use App\Shared\Resource\LocationResource;

final readonly class AccountOrganizationResource
{
    public function __construct(
        public LegalIdentityResource $legalIdentity,
        public ?ImageResource $logo,
        public ?AddressResource $address,
        public ?LocationResource $location,
    ) {
    }
}

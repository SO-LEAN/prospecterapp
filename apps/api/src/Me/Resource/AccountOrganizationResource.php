<?php

namespace App\Me\Resource;

use App\Shared\Resource\ImageResource;
use App\Shared\Resource\LegalIdentityResource;

final readonly class AccountOrganizationResource
{
    public function __construct(
        public LegalIdentityResource $legalIdentity,
        public ?ImageResource $logo,
    ) {
    }
}

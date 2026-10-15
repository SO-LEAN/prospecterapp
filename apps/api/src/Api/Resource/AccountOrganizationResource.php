<?php

namespace App\Api\Resource;

final readonly class AccountOrganizationResource
{
    public function __construct(
        public LegalIdentityResource $legalIdentity,
        public ?ImageResource $logo,
    ) {
    }
}

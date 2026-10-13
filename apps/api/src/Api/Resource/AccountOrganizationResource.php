<?php

namespace App\Api\Resource;

final readonly class AccountOrganizationResource
{
    public function __construct(
        public ?string $corporateName,
        public ?string $form,
        public ?ImageResource $logo,
    ) {
    }
}

<?php

namespace App\Shared\Resource;

final readonly class LegalIdentityResource
{
    public function __construct(
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Shared\Resource;

final readonly class LegalIdentityResource
{
    public function __construct(
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
        public ?string $siret,
    ) {
    }
}

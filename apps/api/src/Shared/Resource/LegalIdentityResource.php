<?php

namespace App\Shared\Resource;

use Prospecter\Core\SharedKernel\Domain\LegalIdentity;

final readonly class LegalIdentityResource
{
    public function __construct(
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
    ) {
    }

    public static function from(LegalIdentity $legalIdentity): self
    {
        return new self($legalIdentity->corporateName, $legalIdentity->form, $legalIdentity->type);
    }
}

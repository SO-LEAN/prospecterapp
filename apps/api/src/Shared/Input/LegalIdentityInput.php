<?php

namespace App\Shared\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LegalIdentityInput
{
    public function __construct(
        #[Assert\Length(max: 150)]
        public ?string $corporateName = null,
        #[Assert\Length(max: 50)]
        public ?string $form = null,
        #[Assert\Length(max: 50)]
        public ?string $type = null,
    ) {
    }
}

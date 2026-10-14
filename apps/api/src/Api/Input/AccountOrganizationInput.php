<?php

namespace App\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AccountOrganizationInput
{
    public function __construct(
        #[Assert\Length(max: 150)]
        public ?string $corporateName = null,
        #[Assert\Length(max: 50)]
        public ?string $form = null,
    ) {
    }
}

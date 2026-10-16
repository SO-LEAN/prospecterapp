<?php

namespace App\PersonalData\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactListQuery
{
    public function __construct(
        #[Assert\Length(max: 100)]
        public string $q = '',
        #[Assert\Length(max: 200)]
        public ?string $after = null,
    ) {
    }
}

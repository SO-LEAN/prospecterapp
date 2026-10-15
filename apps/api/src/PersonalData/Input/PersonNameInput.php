<?php

namespace App\PersonalData\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PersonNameInput
{
    public function __construct(
        #[Assert\Length(max: 100)]
        public ?string $firstName = null,
        #[Assert\Length(max: 100)]
        public ?string $lastName = null,
    ) {
    }
}

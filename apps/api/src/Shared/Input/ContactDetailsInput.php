<?php

namespace App\Shared\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactDetailsInput
{
    public function __construct(
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        public ?string $phoneNumber = null,
        #[Assert\Country]
        public ?string $language = null,
    ) {
    }
}

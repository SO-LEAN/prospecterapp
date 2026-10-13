<?php

namespace App\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Full representation of the current user's account (PUT). A null password keeps the current one.
 */
final readonly class AccountInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        public string $userName = '',
        #[Assert\Length(min: 8, max: 4096)]
        public ?string $password = null,
        #[Assert\Length(max: 100)]
        public ?string $firstName = null,
        #[Assert\Length(max: 100)]
        public ?string $lastName = null,
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        public ?string $phoneNumber = null,
        #[Assert\Country]
        public ?string $language = null,
        #[Assert\Length(max: 150)]
        public ?string $organizationCorporateName = null,
        #[Assert\Length(max: 50)]
        public ?string $organizationForm = null,
    ) {
    }
}

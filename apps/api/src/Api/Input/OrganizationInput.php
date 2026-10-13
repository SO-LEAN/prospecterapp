<?php

namespace App\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Full representation of an organization sent on create and replace (PUT).
 * At least a corporate name or an email is required (checked by the use case).
 */
final readonly class OrganizationInput
{
    public function __construct(
        #[Assert\Length(max: 150)]
        public ?string $corporateName = null,
        #[Assert\Length(max: 50)]
        public ?string $form = null,
        #[Assert\Length(max: 50)]
        public ?string $type = null,
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        public ?string $phoneNumber = null,
        #[Assert\Country]
        public ?string $language = null,
        #[Assert\Length(max: 255)]
        public ?string $street = null,
        #[Assert\Length(max: 255)]
        public ?string $postalCode = null,
        #[Assert\Length(max: 255)]
        public ?string $city = null,
        #[Assert\Country]
        public ?string $country = null,
        #[Assert\Length(max: 255)]
        public ?string $observations = null,
        #[Assert\Positive]
        public ?int $holdBy = null,
    ) {
    }
}

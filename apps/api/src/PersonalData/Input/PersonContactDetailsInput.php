<?php

namespace App\PersonalData\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The contact details of a person: those of an organization, and the profile of the person on LinkedIn.
 */
final readonly class PersonContactDetailsInput
{
    public function __construct(
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        #[OA\Property(description: 'In the international format (+33 3 88 11 22 33) or in the national format of France (03 88 11 22 33); kept in E.164, refused when it is no valid number', example: '03 88 11 22 33')]
        public ?string $phoneNumber = null,
        #[Assert\Country]
        public ?string $language = null,
        #[Assert\Length(max: 255)]
        #[OA\Property(description: 'The address of a profile on LinkedIn, with or without its scheme, its subdomain or what follows its identifier; kept in one form (https://www.linkedin.com/in/<identifier>), refused when it is no profile of a person', example: 'linkedin.com/in/ada-lovelace')]
        public ?string $linkedInProfile = null,
    ) {
    }
}

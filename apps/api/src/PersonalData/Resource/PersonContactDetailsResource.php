<?php

namespace App\PersonalData\Resource;

use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * The contact details of a person: those of an organization, and the profile of the person on LinkedIn.
 */
final readonly class PersonContactDetailsResource
{
    public function __construct(
        public ?string $email,
        #[OA\Property(description: 'In the international format', example: '+33 3 88 11 22 33')]
        public ?string $phoneNumber,
        public ?string $language,
        #[OA\Property(description: 'In one form, its identifier in lower case; stars when masked', example: 'https://www.linkedin.com/in/ada-lovelace')]
        public ?string $linkedInProfile,
    ) {
    }

    public static function of(PersonCard $person): self
    {
        return new self($person->email, $person->phoneNumber, $person->language, $person->linkedInProfile);
    }
}

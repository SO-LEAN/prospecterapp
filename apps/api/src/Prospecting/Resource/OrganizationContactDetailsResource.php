<?php

namespace App\Prospecting\Resource;

use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * The contact details of an organization: those of a person, the page of the company on LinkedIn and its website.
 */
final readonly class OrganizationContactDetailsResource
{
    public function __construct(
        public ?string $email,
        #[OA\Property(description: 'In the international format', example: '+33 3 88 11 22 33')]
        public ?string $phoneNumber,
        public ?string $language,
        #[OA\Property(description: 'In one form, its identifier in lower case', example: 'https://www.linkedin.com/company/acme-corp')]
        public ?string $linkedInPage,
        #[OA\Property(description: 'Its origin, in lower case', example: 'https://www.acme.fr')]
        public ?string $website,
    ) {
    }

    public static function of(OrganizationSheet $organization): self
    {
        return new self($organization->email, $organization->phoneNumber, $organization->language, $organization->linkedInPage, $organization->website);
    }
}

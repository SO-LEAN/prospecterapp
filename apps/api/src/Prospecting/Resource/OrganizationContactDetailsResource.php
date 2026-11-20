<?php

namespace App\Prospecting\Resource;

use App\Shared\Resource\ExternalLinkResource;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * The contact details of an organization: those of a person, its links to the tools outside and its website.
 */
final readonly class OrganizationContactDetailsResource
{
    public function __construct(
        public ?string $email,
        #[OA\Property(description: 'In the international format', example: '+33 3 88 11 22 33')]
        public ?string $phoneNumber,
        public ?string $language,
        /** @var list<ExternalLinkResource> */
        public array $links,
        #[OA\Property(description: 'Its origin, in lower case', example: 'https://www.acme.fr')]
        public ?string $website,
    ) {
    }

    public static function of(OrganizationSheet $organization): self
    {
        return new self($organization->email, $organization->phoneNumber, $organization->language, ExternalLinkResource::allOf($organization->links), $organization->website);
    }
}

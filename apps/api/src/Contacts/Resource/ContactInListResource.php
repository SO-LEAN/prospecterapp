<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * A contact in the list, with the organizations they work for and the tags they carry.
 */
final readonly class ContactInListResource
{
    /**
     * @param list<OrganizationOfContactResource> $organizations
     * @param list<string>                        $tags
     */
    public function __construct(
        public string $id,
        public PersonNameResource $name,
        #[OA\Property(description: 'How to address the contact, x when unknown. It is read unmasked, like their name', enum: ['ms', 'mr', 'x'])]
        public string $title,
        public ?string $jobTitle,
        public PersonContactDetailsResource $contactDetails,
        public bool $masked,
        public array $organizations,
        #[OA\Property(description: 'Every tag the contact carries, their own, inherited and automatic ones, ordered by name', items: new OA\Items(type: 'string', example: '#ESN'))]
        public array $tags,
        #[OA\Property(description: 'The contact, GET /api/contacts/{id}')]
        public LinkResource $contact,
    ) {
    }
}

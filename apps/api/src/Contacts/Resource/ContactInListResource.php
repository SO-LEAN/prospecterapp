<?php

namespace App\Contacts\Resource;

use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * A contact in the list, with the organizations it works for and the tags it carries.
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
        #[OA\Property(description: 'How one addresses the contact, x when unknown, read in clear like their name', enum: ['ms', 'mr', 'x'])]
        public string $title,
        public ?string $jobTitle,
        public PersonContactDetailsResource $contactDetails,
        public bool $masked,
        public array $organizations,
        #[OA\Property(description: 'Every tag it carries, its own, inherited and automatic, in the order of their names', items: new OA\Items(type: 'string', example: '#ESN'))]
        public array $tags,
        #[OA\Property(description: 'The contact, GET /api/contacts/{id}')]
        public LinkResource $contact,
    ) {
    }
}

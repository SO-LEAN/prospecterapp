<?php

namespace App\Contacts\Resource;

use App\PersonalData\Resource\PersonNameResource;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * A contact in the list, with the organizations it works for.
 */
final readonly class ContactInListResource
{
    /**
     * @param list<OrganizationOfContactResource> $organizations
     */
    public function __construct(
        public string $id,
        public PersonNameResource $name,
        public ?string $jobTitle,
        public ContactDetailsResource $contactDetails,
        public bool $masked,
        public array $organizations,
        #[OA\Property(description: 'The contact, GET /api/contacts/{id}')]
        public LinkResource $contact,
    ) {
    }
}

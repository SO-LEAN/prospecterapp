<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class OrganizationContactLinksResource
{
    public function __construct(
        #[OA\Property(description: 'The contact itself, GET /api/contacts/{id}')]
        public LinkResource $contact,
        #[OA\Property(nullable: false)]
        public ?LinkResource $removeFromOrganization = null,
    ) {
    }
}

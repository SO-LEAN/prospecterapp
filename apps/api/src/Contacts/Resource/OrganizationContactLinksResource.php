<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class OrganizationContactLinksResource
{
    public function __construct(
        #[OA\Property(description: 'The contact itself, GET /api/contacts/{id}')]
        public LinkResource $contact,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_remove_contact')]
        public ?LinkResource $removeFromOrganization = null,
    ) {
    }
}

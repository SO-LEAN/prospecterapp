<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class OrganizationOfContactResource
{
    public function __construct(
        public string $id,
        public string $name,
        #[OA\Property(description: 'The organization, GET /api/organizations/{id}')]
        public LinkResource $organization,
    ) {
    }
}

<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ContactListLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'Search by text, with the query parameter q')]
        public ?LinkResource $search = null,
    ) {
    }
}

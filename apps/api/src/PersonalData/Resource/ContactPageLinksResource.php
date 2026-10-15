<?php

namespace App\PersonalData\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ContactPageLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false)]
        public ?LinkResource $collect = null,
        #[OA\Property(nullable: false, description: 'Search by text, with the query parameter q')]
        public ?LinkResource $search = null,
    ) {
    }
}

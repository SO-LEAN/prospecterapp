<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class TaggedContactListLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The next slice, absent when this one is the last')]
        public ?LinkResource $next = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ContactListLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'Search by text, with the query parameter q')]
        public ?LinkResource $search = null,
        #[OA\Property(nullable: false, description: 'The next slice, absent when this one is the last')]
        public ?LinkResource $next = null,
        #[OA\Property(nullable: false, description: 'Search the contacts that carry every chosen tag, with the query parameter tags')]
        public ?LinkResource $searchByTags = null,
    ) {
    }
}

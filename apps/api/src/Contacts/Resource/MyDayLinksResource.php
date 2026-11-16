<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class MyDayLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'The search of the contacts to plan an exchange with, by their name')]
        public LinkResource $searchContacts,
    ) {
    }
}

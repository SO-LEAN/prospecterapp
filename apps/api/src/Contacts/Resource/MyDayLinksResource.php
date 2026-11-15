<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class MyDayLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The search of the contacts to plan an exchange with, absent when the member may not plan: without the right to manage personal data')]
        public ?LinkResource $searchContacts = null,
    ) {
    }
}

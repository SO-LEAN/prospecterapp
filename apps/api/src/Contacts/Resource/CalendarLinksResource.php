<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class CalendarLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The search of the contacts to plan an exchange with in a free slot, absent when the member may not plan, every prospector planning (ADR 0129)')]
        public ?LinkResource $searchContacts = null,
    ) {
    }
}

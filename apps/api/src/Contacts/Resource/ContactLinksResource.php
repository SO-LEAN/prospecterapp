<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ContactLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false)]
        public ?LinkResource $rename = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeJobTitle = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeContactDetails = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $forget = null,
        #[OA\Property(description: 'The exchanges with the contact, the most recent first (ADR 0074)', nullable: false)]
        public ?LinkResource $exchanges = null,
    ) {
    }
}

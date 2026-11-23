<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class CalendarLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The search for contacts to plan an exchange with in a free slot. It is absent when the member may not plan. Every prospector may plan (ADR 0129)')]
        public ?LinkResource $searchContacts = null,
    ) {
    }
}

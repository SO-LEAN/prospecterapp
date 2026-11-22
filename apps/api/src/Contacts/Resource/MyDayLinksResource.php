<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class MyDayLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The search of the contacts to plan an exchange with, absent when the member may not plan, every prospector planning (ADR 0129)')]
        public ?LinkResource $searchContacts = null,
    ) {
    }
}

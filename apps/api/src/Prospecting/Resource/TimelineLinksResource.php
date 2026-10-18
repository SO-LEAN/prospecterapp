<?php

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class TimelineLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $noteExchange,
        #[OA\Property(nullable: false, description: 'The next slice, absent when this one is the last')]
        public ?LinkResource $next = null,
    ) {
    }
}

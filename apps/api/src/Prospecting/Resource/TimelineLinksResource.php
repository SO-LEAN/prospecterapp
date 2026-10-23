<?php

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class TimelineLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The next slice, absent when this one is the last')]
        public ?LinkResource $next = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $noteExchange = null,
        #[OA\Property(nullable: false, description: 'The exchanges of an organization to make conform, their contact forgotten, absent while there is none')]
        public ?LinkResource $toMakeConform = null,
    ) {
    }

    public function withToMakeConform(?LinkResource $toMakeConform): self
    {
        return new self($this->self, $this->next, $this->noteExchange, $toMakeConform);
    }
}

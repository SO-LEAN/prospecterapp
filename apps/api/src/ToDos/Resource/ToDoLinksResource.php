<?php

namespace App\ToDos\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ToDoLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Change its text and when it is due', nullable: false)]
        public ?LinkResource $revise = null,
        #[OA\Property(description: 'Tick it off, while it is to do', nullable: false)]
        public ?LinkResource $tickOff = null,
        #[OA\Property(description: 'Untick it, once done', nullable: false)]
        public ?LinkResource $untick = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $erase = null,
    ) {
    }
}

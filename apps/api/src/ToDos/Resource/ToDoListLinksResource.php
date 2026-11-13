<?php

namespace App\ToDos\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ToDoListLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Note a to-do for the contact or the organization', nullable: false)]
        public ?LinkResource $noteToDo = null,
    ) {
    }
}

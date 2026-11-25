<?php

declare(strict_types=1);

namespace App\ToDos\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class ToDoListLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Log a to-do for the contact or the organization', nullable: false)]
        #[Offers('api_contacts_note_to_do', 'api_organizations_note_to_do')]
        public ?LinkResource $noteToDo = null,
    ) {
    }
}

<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class PossibleDuplicateOfContactLinksResource
{
    public function __construct(
        #[OA\Property(description: 'The other contact')]
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'Say, once checked, that the contact is no duplicate of the other one')]
        public ?LinkResource $dismiss = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class PossibleDuplicateOfContactLinksResource
{
    public function __construct(
        #[OA\Property(description: 'The other contact')]
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'Say, once checked, that the contact is not a duplicate of the other one')]
        #[Offers('api_contacts_dismiss_possible_duplicate')]
        public ?LinkResource $dismiss = null,
    ) {
    }
}

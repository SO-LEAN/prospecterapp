<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class PossibleDuplicateLinksResource
{
    public function __construct(
        #[OA\Property(description: 'The other organization')]
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'States, after a check, that it is not a duplicate of the other organization')]
        #[Offers('api_organizations_dismiss_possible_duplicate')]
        public ?LinkResource $dismiss = null,
    ) {
    }
}

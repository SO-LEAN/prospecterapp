<?php

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class PossibleDuplicateLinksResource
{
    public function __construct(
        #[OA\Property(description: 'The other organization')]
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'Say, once checked, that it is no duplicate of the other organization')]
        public ?LinkResource $dismiss = null,
    ) {
    }
}

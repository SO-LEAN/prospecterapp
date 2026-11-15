<?php

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class GoogleClientLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Configure the client, or another one, with its id and its secret')]
        public LinkResource $configure,
        #[OA\Property(description: 'Remove the client, while the account has one', nullable: false)]
        public ?LinkResource $remove = null,
    ) {
    }
}

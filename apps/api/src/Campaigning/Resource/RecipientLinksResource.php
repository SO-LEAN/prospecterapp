<?php

namespace App\Campaigning\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class RecipientLinksResource
{
    public function __construct(
        #[OA\Property(description: 'Take them off the campaign, while it is frozen', nullable: false)]
        public ?LinkResource $remove = null,
    ) {
    }
}

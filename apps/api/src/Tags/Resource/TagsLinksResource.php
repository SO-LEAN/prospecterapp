<?php

namespace App\Tags\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class TagsLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Put tags, as typed: each written one way, an automatic one refused')]
        public LinkResource $tag,
    ) {
    }
}

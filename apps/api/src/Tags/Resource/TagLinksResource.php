<?php

declare(strict_types=1);

namespace App\Tags\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class TagLinksResource
{
    public function __construct(
        #[OA\Property(description: 'Take the tag off')]
        public LinkResource $takeOff,
    ) {
    }
}

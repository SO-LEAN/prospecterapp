<?php

declare(strict_types=1);

namespace App\Tags\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class TagLinksResource
{
    public function __construct(
        #[OA\Property(description: 'Take the tag off')]
        #[Offers('api_contacts_take_off_tag', 'api_organizations_take_off_tag')]
        public LinkResource $takeOff,
    ) {
    }
}

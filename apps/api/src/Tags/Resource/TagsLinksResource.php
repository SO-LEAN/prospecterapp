<?php

declare(strict_types=1);

namespace App\Tags\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class TagsLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Put tags, as typed. Each tag is written one way, and an automatic tag is refused')]
        #[Offers('api_contacts_tag', 'api_organizations_tag')]
        public LinkResource $tag,
    ) {
    }
}

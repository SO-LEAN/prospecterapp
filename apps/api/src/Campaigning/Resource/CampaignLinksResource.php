<?php

namespace App\Campaigning\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class CampaignLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Change its name, its channel, its tags and its message, while a draft', nullable: false)]
        public ?LinkResource $revise = null,
        #[OA\Property(description: 'Discard it, while a draft', nullable: false)]
        public ?LinkResource $discard = null,
        #[OA\Property(description: 'The contacts it targets: the search by its targeted tags', nullable: false)]
        public ?LinkResource $target = null,
    ) {
    }
}

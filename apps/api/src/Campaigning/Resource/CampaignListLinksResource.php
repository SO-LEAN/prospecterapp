<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class CampaignListLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Prepare a campaign')]
        #[Offers('api_campaigns_prepare')]
        public LinkResource $prepare,
    ) {
    }
}

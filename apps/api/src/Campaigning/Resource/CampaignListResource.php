<?php

namespace App\Campaigning\Resource;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Every campaign of the team, the newest first, each with the contacts it targets counted (ADR 0144).
 */
final readonly class CampaignListResource
{
    /**
     * @param list<CampaignResource> $items
     */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: CampaignResource::class)))]
        public array $items,
        #[SerializedName('_links')]
        public CampaignListLinksResource $links,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/** Every recipient of a frozen campaign, in the order added, 500 at most. The list is read as a whole. */
final readonly class RecipientListResource
{
    /**
     * @param list<RecipientResource> $items
     */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: RecipientResource::class)))]
        public array $items,
        #[SerializedName('_links')]
        public RecipientListLinksResource $links,
    ) {
    }
}

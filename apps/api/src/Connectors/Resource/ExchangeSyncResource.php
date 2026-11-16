<?php

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Where the push of an exchange into my calendar at Google stands (ADR 0133), none while it is not pushed into mine:
 * without a connection, an exchange of another member, or one past never pushed.
 */
final readonly class ExchangeSyncResource
{
    public function __construct(
        #[OA\Property(nullable: true)]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ?SyncResource $sync,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ExchangeSyncLinksResource $links,
    ) {
    }
}

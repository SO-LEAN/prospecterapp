<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

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
        public ?SyncResource $sync,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ExchangeSyncLinksResource $links,
    ) {
    }
}

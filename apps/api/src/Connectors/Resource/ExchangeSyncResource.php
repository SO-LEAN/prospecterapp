<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * An exchange's push state in my Google calendar (ADR 0133). There is none while the exchange is not pushed into my
 * calendar: I have no connection, the exchange is another member's, or it is past and was never pushed.
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

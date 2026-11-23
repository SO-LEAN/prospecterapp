<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * My connections to outside services. Google is the only one so far. There is none until I connect.
 */
final readonly class ConnectionsResource
{
    /**
     * @param list<ConnectionResource> $connections
     */
    public function __construct(
        public array $connections,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ConnectionsLinksResource $links,
    ) {
    }
}

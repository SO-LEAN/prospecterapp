<?php

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A connection of mine to a provider: connected, or to reconnect once the provider refused its access.
 */
final readonly class ConnectionResource
{
    public function __construct(
        #[OA\Property(enum: ['google'])]
        public string $provider,
        #[OA\Property(enum: ['connected', 'toReconnect'])]
        public string $state,
        #[OA\Property(description: 'The account connected at the provider', format: 'email')]
        public string $account,
        public \DateTimeImmutable $connectedAt,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ConnectionLinksResource $links,
    ) {
    }
}

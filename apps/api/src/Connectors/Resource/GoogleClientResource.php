<?php

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The client of Google of my account, as its administrator configures it (ADR 0125).
 */
final readonly class GoogleClientResource
{
    public function __construct(
        #[OA\Property(description: 'The id of the client at Google, none while the account has no client')]
        public ?string $clientId,
        #[OA\Property(description: 'Whether its secret is set: it is never answered')]
        public bool $secretSet,
        #[OA\Property(description: 'The redirect URI to declare in the client at Google', format: 'uri')]
        public string $redirectUri,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public GoogleClientLinksResource $links,
    ) {
    }
}

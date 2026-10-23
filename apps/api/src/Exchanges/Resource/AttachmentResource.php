<?php

namespace App\Exchanges\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A file joined to an exchange: its link `download` answers where to read it for a moment.
 */
final readonly class AttachmentResource
{
    public function __construct(
        public string $id,
        #[OA\Property(description: 'Its name as it was sent')]
        public string $name,
        #[OA\Property(description: 'In bytes')]
        public int $size,
        public string $mediaType,
        #[OA\Property(description: 'The contact of the exchange forgotten, it may hold their personal data: made conform by removing it')]
        public bool $toMakeConform,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public AttachmentLinksResource $links,
    ) {
    }
}

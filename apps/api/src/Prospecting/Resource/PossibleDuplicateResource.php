<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * An organization of the account that the organization resembles by its name and its city. They may be the same.
 */
final readonly class PossibleDuplicateResource
{
    public function __construct(
        public string $id,
        public string $name,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public PossibleDuplicateLinksResource $links,
    ) {
    }
}

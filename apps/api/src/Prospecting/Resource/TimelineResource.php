<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A slice of the exchanges with a contact, the most recent moment first (ADR 0049, 0074). The `next` link leads to the
 * next slice.
 */
final readonly class TimelineResource
{
    /**
     * @param list<ExchangeResource> $items
     */
    public function __construct(
        public array $items,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public TimelineLinksResource $links,
    ) {
    }
}

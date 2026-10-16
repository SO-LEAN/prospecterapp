<?php

namespace App\Prospecting\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A slice of the organizations, newest first (ADR 0049): the next one is reached by the link next.
 */
final readonly class OrganizationPageResource
{
    /**
     * @param list<OrganizationSummaryResource> $items
     */
    public function __construct(
        public array $items,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public OrganizationPageLinksResource $links,
    ) {
    }
}

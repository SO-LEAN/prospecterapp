<?php

namespace App\Contacts\Resource;

use App\Prospecting\Resource\TimelineLinksResource;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A slice of the exchanges with an organization and with its contacts, the most recent moment first (ADR 0049,
 * 0076): the next one is reached by the link next.
 */
final readonly class OrganizationTimelineResource
{
    /**
     * @param list<OrganizationExchangeResource> $items
     */
    public function __construct(
        public array $items,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public TimelineLinksResource $links,
    ) {
    }
}

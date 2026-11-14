<?php

namespace App\Contacts\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The exchanges the signed-in member holds in a period, planned or noted by them, whatever their status, by their
 * moment, each with who it is with; the search of the contacts to plan with in a free slot when they may plan.
 */
final readonly class CalendarResource
{
    /**
     * @param list<OrganizationExchangeResource> $items
     */
    public function __construct(
        public array $items,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public CalendarLinksResource $links,
    ) {
    }
}

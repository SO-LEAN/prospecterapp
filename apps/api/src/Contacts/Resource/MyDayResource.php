<?php

namespace App\Contacts\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The day of the signed-in member: the exchanges they planned today and still planned, then the next five planned
 * after it, the soonest first, each with who it is with and their organization; the search of the contacts to plan
 * with when they may plan.
 */
final readonly class MyDayResource
{
    /**
     * @param list<DayExchangeResource> $today
     * @param list<DayExchangeResource> $next
     */
    public function __construct(
        public array $today,
        public array $next,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public MyDayLinksResource $links,
    ) {
    }
}

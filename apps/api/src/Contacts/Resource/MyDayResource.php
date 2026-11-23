<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The signed-in member's day: the exchanges planned today that are still planned, then the next five planned after
 * today, the soonest first. Each one comes with who it is with and their organization. When the member may plan, the
 * day links to the search for contacts to plan an exchange with.
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

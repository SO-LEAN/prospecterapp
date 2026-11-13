<?php

namespace App\Contacts\Resource;

/**
 * Exchanges the signed-in member planned, the soonest first, each with who it is with.
 */
final readonly class PlannedExchangesResource
{
    /**
     * @param list<OrganizationExchangeResource> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}

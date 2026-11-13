<?php

namespace App\Contacts\Resource;

/**
 * The exchanges the signed-in member holds in a period, planned or noted by them, whatever their status, by their
 * moment, each with who it is with.
 */
final readonly class CalendarResource
{
    /**
     * @param list<OrganizationExchangeResource> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}

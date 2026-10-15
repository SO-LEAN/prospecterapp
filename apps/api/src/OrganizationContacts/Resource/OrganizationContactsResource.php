<?php

namespace App\OrganizationContacts\Resource;

final readonly class OrganizationContactsResource
{
    /**
     * @param list<OrganizationContactResource> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}

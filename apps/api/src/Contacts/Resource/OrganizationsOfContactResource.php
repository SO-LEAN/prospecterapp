<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

final readonly class OrganizationsOfContactResource
{
    /**
     * @param list<OrganizationOfContactResource> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}

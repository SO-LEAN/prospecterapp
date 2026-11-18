<?php

namespace App\Contacts\Resource;

final readonly class OrganizationProposedResource
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}

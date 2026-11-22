<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

final readonly class OrganizationProposedResource
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}

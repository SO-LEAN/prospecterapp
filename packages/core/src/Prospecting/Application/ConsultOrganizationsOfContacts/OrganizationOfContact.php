<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts;

use Prospecter\Core\Prospecting\Domain\Organization;

final readonly class OrganizationOfContact
{
    private function __construct(
        public string $id,
        public string $fullName,
    ) {
    }

    public static function of(Organization $organization): self
    {
        return new self($organization->id()->value, $organization->fullName());
    }
}

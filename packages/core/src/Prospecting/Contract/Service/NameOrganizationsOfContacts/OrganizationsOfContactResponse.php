<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts;

/**
 * The published language of the organizations that name a contact (ADR 0045, 0144). It gives their full names in
 * the order Prospecting lists them, the first organization first.
 */
final readonly class OrganizationsOfContactResponse
{
    /**
     * @param list<string> $organizationNames
     */
    public function __construct(
        public string $personId,
        public array $organizationNames,
    ) {
    }
}

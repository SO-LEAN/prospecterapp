<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts;

/**
 * The published language of the organizations naming a contact (ADR 0045, 0144): their full names, the first one
 * first, as Prospecting lists them.
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

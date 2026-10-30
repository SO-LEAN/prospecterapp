<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts;

/**
 * An organization a contact is part of, as the list of the contacts names it.
 */
final readonly class OrganizationOfContact
{
    public function __construct(
        public string $id,
        public string $fullName,
    ) {
    }
}

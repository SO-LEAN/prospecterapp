<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts;

/**
 * An organization a contact is part of, as the contact list names it.
 */
final readonly class OrganizationOfContact
{
    public function __construct(
        public string $id,
        public string $fullName,
    ) {
    }
}

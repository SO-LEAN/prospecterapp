<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts;

/**
 * An organization a contact is part of, as the contact list names it, and when the contact was attached to it. An
 * attachment recorded before Prospecting kept the moment has none.
 */
final readonly class OrganizationOfContact
{
    public function __construct(
        public string $id,
        public string $fullName,
        public ?\DateTimeImmutable $attachedAt,
    ) {
    }
}

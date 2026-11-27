<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts;

/**
 * The published language of one organization that names a contact (ADR 0045, 0144): its id, its full name, and when
 * the contact was attached to it. An attachment that Prospecting recorded before it kept the moment has none.
 */
final readonly class OrganizationOfContactResponse
{
    public function __construct(
        public string $id,
        public string $fullName,
        public ?\DateTimeImmutable $attachedAt,
    ) {
    }
}

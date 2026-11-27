<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts;

/**
 * Reads which persons are an organization's contacts, by their ids (ADR 0217).
 */
final readonly class ListOrganizationContacts
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts;

/**
 * Reads an organization's contacts as the reader reads them (ADR 0217).
 */
final readonly class ListOrganizationContacts
{
    public function __construct(
        public string $prospectorId,
        public string $organizationId,
    ) {
    }
}

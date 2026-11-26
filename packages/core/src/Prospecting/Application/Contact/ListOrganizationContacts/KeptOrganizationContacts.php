<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptOrganizationContacts implements ListOrganizationContactsOutput
{
    public private(set) ?OrganizationContacts $contacts = null;

    public function present(OrganizationContacts $contacts): void
    {
        $this->contacts = $contacts;
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptOrganizationsNamingContacts implements WhichOrganizationsNameTheseContactsOutput
{
    /**
     * @var list<OrganizationsNamingContactResponse>
     */
    public private(set) array $contacts = [];

    public function present(array $contacts): void
    {
        $this->contacts = $contacts;
    }
}

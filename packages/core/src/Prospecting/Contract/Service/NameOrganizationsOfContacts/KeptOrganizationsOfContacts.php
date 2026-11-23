<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptOrganizationsOfContacts implements NameOrganizationsOfContactsOutput
{
    /**
     * @var list<OrganizationsOfContactResponse>
     */
    public private(set) array $contacts = [];

    public function present(array $contacts): void
    {
        $this->contacts = $contacts;
    }
}

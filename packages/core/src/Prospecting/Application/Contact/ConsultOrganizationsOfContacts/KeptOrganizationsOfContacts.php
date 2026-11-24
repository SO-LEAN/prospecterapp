<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptOrganizationsOfContacts implements ConsultOrganizationsOfContactsOutput
{
    /**
     * @var array<string, list<OrganizationOfContact>>
     */
    public private(set) array $organizations;

    /**
     * @param array<string, list<OrganizationOfContact>> $organizations by person id, with every person asked about
     */
    public function present(array $organizations): void
    {
        $this->organizations = $organizations;
    }
}

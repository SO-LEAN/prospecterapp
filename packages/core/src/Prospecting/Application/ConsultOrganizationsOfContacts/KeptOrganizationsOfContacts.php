<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptOrganizationsOfContacts implements ConsultOrganizationsOfContactsOutput
{
    /**
     * @var array<string, list<OrganizationOfContact>>
     */
    public private(set) array $organizations;

    /**
     * @param array<string, list<OrganizationOfContact>> $organizations by person id, every asked person included
     */
    public function present(array $organizations): void
    {
        $this->organizations = $organizations;
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultOrganizationsOfContactsOutput extends OutputPort
{
    /**
     * @param array<string, list<OrganizationOfContact>> $organizations by person id, every asked person included
     */
    public function present(array $organizations): void;
}

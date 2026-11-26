<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\IdentifyPersonsAndOrganizations;

use Prospecter\Core\PersonalData\Application\Identification\Identification;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface IdentifyPersonsAndOrganizationsOutput extends OutputPort
{
    /**
     * @param list<Identification> $persons       one per person in the query, in the query's order
     * @param list<Identification> $organizations one per organization in the query, in the query's order
     */
    public function present(array $persons, array $organizations): void;
}

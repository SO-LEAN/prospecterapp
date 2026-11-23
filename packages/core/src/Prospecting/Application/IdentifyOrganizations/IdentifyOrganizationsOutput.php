<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\IdentifyOrganizations;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface IdentifyOrganizationsOutput extends OutputPort
{
    /**
     * @param list<Identification> $identifications one per organization in the query, in the query's order
     */
    public function present(array $identifications): void;
}

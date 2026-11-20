<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\IdentifyOrganizations;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface IdentifyOrganizationsOutput extends OutputPort
{
    /**
     * @param list<Identification> $identifications one per organization of the query, in its order
     */
    public function present(array $identifications): void;
}

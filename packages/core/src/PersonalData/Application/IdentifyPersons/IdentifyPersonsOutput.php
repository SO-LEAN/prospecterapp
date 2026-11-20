<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyPersons;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface IdentifyPersonsOutput extends OutputPort
{
    /**
     * @param list<Identification> $identifications one per person of the query, in its order
     */
    public function present(array $identifications): void;
}

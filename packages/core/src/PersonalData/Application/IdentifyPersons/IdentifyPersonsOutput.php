<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyPersons;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface IdentifyPersonsOutput extends OutputPort
{
    /**
     * @param list<Identification> $identifications one per person in the query, in the query's order
     */
    public function present(array $identifications): void;
}

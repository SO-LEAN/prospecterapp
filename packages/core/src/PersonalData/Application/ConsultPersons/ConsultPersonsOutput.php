<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPersons;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultPersonsOutput extends OutputPort
{
    /**
     * @param array<string, PersonResponse> $persons by person id
     */
    public function present(array $persons): void;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPersons;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultPersonsOutput extends OutputPort
{
    /**
     * @param array<string, PersonCard> $persons by person id
     */
    public function present(array $persons): void;
}

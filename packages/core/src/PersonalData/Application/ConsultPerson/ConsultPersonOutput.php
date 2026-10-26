<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPerson;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultPersonOutput extends OutputPort
{
    public function present(PersonResponse $person): void;
}

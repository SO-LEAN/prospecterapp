<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPerson;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultPersonOutput extends OutputPort
{
    public function present(PersonCard $person): void;
}

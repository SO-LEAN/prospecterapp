<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RecognizeCallingContact;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface RecognizeCallingContactOutput extends OutputPort
{
    /**
     * @param list<PersonResponse> $contacts the contacts having the number
     */
    public function present(array $contacts): void;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RecognizeCallingContact;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface RecognizeCallingContactOutput extends OutputPort
{
    /**
     * @param list<PersonCard> $contacts the contacts having the number
     */
    public function present(array $contacts): void;
}

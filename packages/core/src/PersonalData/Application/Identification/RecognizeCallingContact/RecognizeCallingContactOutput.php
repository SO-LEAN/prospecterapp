<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\RecognizeCallingContact;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface RecognizeCallingContactOutput extends OutputPort
{
    /**
     * @param list<PersonCard> $contacts the contacts who have the number
     */
    public function present(array $contacts): void;
}

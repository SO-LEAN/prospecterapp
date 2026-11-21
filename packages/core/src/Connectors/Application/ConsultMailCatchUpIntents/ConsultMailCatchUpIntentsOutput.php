<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultMailCatchUpIntentsOutput extends OutputPort
{
    /**
     * @param list<string> $possibleIntents names of what may be done: CatchUpGmail, or nothing
     */
    public function present(array $possibleIntents): void;
}

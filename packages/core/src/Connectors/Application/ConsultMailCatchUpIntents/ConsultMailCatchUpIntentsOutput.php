<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultMailCatchUpIntentsOutput extends OutputPort
{
    /**
     * @param list<string> $possibleIntents the names of the possible intents: CatchUpGmail, or none
     */
    public function present(array $possibleIntents): void;
}

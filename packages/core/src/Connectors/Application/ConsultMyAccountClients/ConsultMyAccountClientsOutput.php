<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMyAccountClients;

use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultMyAccountClientsOutput extends OutputPort
{
    /**
     * @param list<AccountClient> $clients
     */
    public function present(array $clients): void;
}

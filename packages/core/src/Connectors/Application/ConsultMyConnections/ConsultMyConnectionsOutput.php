<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMyConnections;

use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultMyConnectionsOutput extends OutputPort
{
    /**
     * @param list<MyConnection>  $connections
     * @param list<AccountClient> $offered     the providers that I may connect to, each with my account's client there
     */
    public function present(array $connections, array $offered): void;
}

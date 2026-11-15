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
     * @param list<AccountClient> $offered     the providers I may connect to, by the client of my account there
     */
    public function present(array $connections, array $offered): void;
}

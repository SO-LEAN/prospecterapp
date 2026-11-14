<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMyConnections;

use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultMyConnectionsOutput extends OutputPort
{
    /**
     * @param list<MyConnection> $connections
     */
    public function present(array $connections): void;
}

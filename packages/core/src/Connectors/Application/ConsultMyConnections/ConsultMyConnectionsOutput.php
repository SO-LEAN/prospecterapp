<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMyConnections;

use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultMyConnectionsOutput extends OutputPort
{
    /**
     * @param list<MyConnection> $connections
     * @param list<string>       $offered     the providers I may connect to (`google`), those a client serves
     */
    public function present(array $connections, array $offered): void;
}

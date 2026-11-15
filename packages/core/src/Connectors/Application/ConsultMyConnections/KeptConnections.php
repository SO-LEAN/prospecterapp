<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMyConnections;

use Prospecter\Core\Connectors\Application\MyConnection;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptConnections implements ConsultMyConnectionsOutput
{
    /** @var list<MyConnection> */
    public private(set) array $connections = [];
    /** @var list<string> */
    public private(set) array $offered = [];

    public function present(array $connections, array $offered): void
    {
        $this->connections = $connections;
        $this->offered = $offered;
    }
}

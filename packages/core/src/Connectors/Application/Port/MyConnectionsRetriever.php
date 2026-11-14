<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Port;

use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the connections of a member without restoring them (ADR 0088), in the order of their providers.
 */
interface MyConnectionsRetriever
{
    /**
     * @return list<MyConnection>
     */
    public function of(MemberId $me): array;
}

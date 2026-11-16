<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Port;

use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the connections of a member without restoring them (ADR 0088), in the order of their providers, each counting
 * the entries of their exchanges starting from a moment on, synced and failed (ADR 0133).
 */
interface MyConnectionsRetriever
{
    /**
     * @return list<MyConnection>
     */
    public function of(MemberId $me, \DateTimeImmutable $from): array;
}

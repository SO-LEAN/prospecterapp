<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Port;

use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads a member's connections without restoring them (ADR 0088), in their providers' order. Each connection counts
 * the entries of its exchanges from a given moment on, synced and failed (ADR 0133).
 */
interface MyConnectionsRetriever
{
    /**
     * @return list<MyConnection>
     */
    public function of(MemberId $me, \DateTimeImmutable $from): array;
}

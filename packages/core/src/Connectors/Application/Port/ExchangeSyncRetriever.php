<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Port;

use Prospecter\Core\Connectors\Application\ExchangeSync;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads an exchange's push state from its entry, without restoring the entry (ADR 0088, 0133).
 */
interface ExchangeSyncRetriever
{
    /**
     * Returns null while nothing of the exchange was pushed into the member's calendar.
     */
    public function find(ExchangeId $exchange, MemberId $member): ?ExchangeSync;
}

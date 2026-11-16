<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Port;

use Prospecter\Core\Connectors\Application\ExchangeSync;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads where the push of an exchange stands from its entry, without restoring it (ADR 0088, 0133).
 */
interface ExchangeSyncRetriever
{
    /**
     * None while nothing was pushed of it into the calendar of the member.
     */
    public function find(ExchangeId $exchange, MemberId $member): ?ExchangeSync;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\Port;

use Prospecter\Core\Booking\Application\MyBookingPage;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Port: reads a host's booking page without restoring it (ADR 0088).
 */
interface MyBookingPageRetriever
{
    /**
     * @param string $today YYYY-MM-DD, the reading's day on the host's clock. The periods that ended before it are left out
     */
    public function of(MemberId $host, string $today, bool $calendarConnected): MyBookingPage;
}

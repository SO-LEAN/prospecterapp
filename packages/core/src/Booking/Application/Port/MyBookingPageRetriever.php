<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\Port;

use Prospecter\Core\Booking\Application\MyBookingPage;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the booking page of a host, read without restoring it (ADR 0088).
 */
interface MyBookingPageRetriever
{
    /**
     * @param string $today YYYY-MM-DD, the day of the reading on the clock of the host: the periods ended before it are not read
     */
    public function of(MemberId $host, string $today, bool $calendarConnected): MyBookingPage;
}

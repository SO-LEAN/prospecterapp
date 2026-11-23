<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\Port;

use Prospecter\Core\Booking\Application\PublicLink\ConsultPublicBookingPage\BookingSchedule;

/**
 * Port: reads the booking page of a code without restoring it (ADR 0088). A former code, or a code nobody drew, leads
 * to no page.
 */
interface PublicBookingPageRetriever
{
    public function byCode(string $code): ?BookingSchedule;
}

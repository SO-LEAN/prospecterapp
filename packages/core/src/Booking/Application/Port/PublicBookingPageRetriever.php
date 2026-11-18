<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\Port;

use Prospecter\Core\Booking\Application\ConsultPublicBookingPage\BookingSchedule;

/**
 * Port: the booking page of a code, read without restoring it (ADR 0088); none for a former code, nor one nobody drew.
 */
interface PublicBookingPageRetriever
{
    public function byCode(string $code): ?BookingSchedule;
}

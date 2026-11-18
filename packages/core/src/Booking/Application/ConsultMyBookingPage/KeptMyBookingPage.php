<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultMyBookingPage;

use Prospecter\Core\Booking\Application\MyBookingPage;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptMyBookingPage implements ConsultMyBookingPageOutput
{
    public private(set) ?MyBookingPage $page = null;

    public function present(MyBookingPage $page): void
    {
        $this->page = $page;
    }
}

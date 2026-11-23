<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\PublicLink\ConsultPublicBookingPage;

use Prospecter\Core\Booking\Application\PublicLink\PublicBookingPage;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptPublicBookingPage implements ConsultPublicBookingPageOutput
{
    public private(set) ?PublicBookingPage $page = null;

    public function present(PublicBookingPage $page): void
    {
        $this->page = $page;
    }
}

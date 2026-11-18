<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultPublicBookingPage;

use Prospecter\Core\Booking\Application\PublicBookingPage;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultPublicBookingPageOutput extends OutputPort
{
    public function present(PublicBookingPage $page): void;
}

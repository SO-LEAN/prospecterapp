<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\PublicLink\ConsultPublicBookingPage;

use Prospecter\Core\Booking\Application\PublicLink\PublicBookingPage;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultPublicBookingPageOutput extends OutputPort
{
    public function present(PublicBookingPage $page): void;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultMyBookingPage;

use Prospecter\Core\Booking\Application\MyBookingPage;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultMyBookingPageOutput extends OutputPort
{
    public function present(MyBookingPage $page): void;
}

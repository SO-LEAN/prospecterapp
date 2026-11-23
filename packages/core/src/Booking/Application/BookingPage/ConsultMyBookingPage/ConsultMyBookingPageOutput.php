<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\BookingPage\ConsultMyBookingPage;

use Prospecter\Core\Booking\Application\BookingPage\MyBookingPage;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultMyBookingPageOutput extends OutputPort
{
    public function present(MyBookingPage $page): void;
}

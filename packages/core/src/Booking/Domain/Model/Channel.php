<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * How a booker may meet the host: a call, or a video call whose link the calendar of the host creates.
 */
enum Channel: string
{
    case Call = 'call';
    case VideoCall = 'videoCall';
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * How a booker can meet the host: a call, or a video call. The host's calendar creates the video call's link.
 */
enum Channel: string
{
    case Call = 'call';
    case VideoCall = 'videoCall';
}

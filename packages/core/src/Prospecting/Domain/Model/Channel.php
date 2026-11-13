<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * How the prospector and the contact exchanged.
 */
enum Channel: string
{
    case Call = 'call';
    case Sms = 'sms';
    case Email = 'email';
    case LinkedIn = 'linkedIn';
    case Meeting = 'meeting';
    case VideoCall = 'videoCall';
    case Other = 'other';

    /**
     * Only a call, a meeting or a video call is agreed upon beforehand: a message is just sent.
     */
    public function mayBePlanned(): bool
    {
        return in_array($this, [self::Call, self::Meeting, self::VideoCall], true);
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

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
    case Other = 'other';
}

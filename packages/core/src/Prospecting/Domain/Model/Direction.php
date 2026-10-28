<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * Who reached out: the contact (inbound), or the prospector (outbound).
 */
enum Direction: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * Who called off a planned exchange, when the prospector says it.
 */
enum Canceller: string
{
    case Prospector = 'prospector';
    case Contact = 'contact';
}

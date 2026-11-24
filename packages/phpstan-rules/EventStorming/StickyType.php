<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * The colours of the event storming.
 */
enum StickyType: string
{
    case Actor = 'actor';
    case Command = 'command';
    case Aggregate = 'aggregate';
    case Event = 'event';
    case Policy = 'policy';
    case ReadModel = 'readModel';
    case ExternalSystem = 'externalSystem';
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * How a planned exchange takes place, as its event names it.
 */
enum Channel: string
{
    case Call = 'call';
    case Meeting = 'meeting';
    case VideoCall = 'videoCall';
}

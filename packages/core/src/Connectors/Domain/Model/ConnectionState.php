<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * Whether the provider still grants the access it gave. Once it refuses the token, the member must connect again.
 */
enum ConnectionState: string
{
    case Connected = 'connected';
    case ToReconnect = 'toReconnect';
}

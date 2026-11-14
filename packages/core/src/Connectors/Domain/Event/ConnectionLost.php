<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The provider refused the token of a connection: the member is to connect again.
 */
final readonly class ConnectionLost extends ConnectionEvent
{
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The provider refused a connection's token. The member has to connect again.
 */
final readonly class ConnectionLost extends ConnectionEvent
{
}

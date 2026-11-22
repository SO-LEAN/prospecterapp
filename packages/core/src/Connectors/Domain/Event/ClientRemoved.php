<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * An administrator removed the client of their account at a provider: it is no longer offered to its members.
 */
final readonly class ClientRemoved extends ClientEvent
{
}

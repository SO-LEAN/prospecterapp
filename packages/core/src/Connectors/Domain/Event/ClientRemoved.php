<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * An administrator removed their account's client at a provider. The account's members are no longer offered it.
 */
final readonly class ClientRemoved extends ClientEvent
{
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * A member disconnected their account at a provider. The access was revoked there.
 */
final readonly class Disconnected extends ConnectionEvent
{
}

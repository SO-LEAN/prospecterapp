<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * A member connected their account at a provider, for the first time or again.
 */
final readonly class Connected extends ConnectionEvent
{
}

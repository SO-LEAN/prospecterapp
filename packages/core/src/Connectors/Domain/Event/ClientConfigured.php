<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\AccountEvent;

/**
 * An administrator configured the client of their account at a provider, for the first time or another one.
 */
final readonly class ClientConfigured extends AccountEvent
{
}

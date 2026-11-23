<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * An administrator configured their account's client at a provider. It is the first client, or it replaces another.
 */
final readonly class ClientConfigured extends ClientEvent
{
}

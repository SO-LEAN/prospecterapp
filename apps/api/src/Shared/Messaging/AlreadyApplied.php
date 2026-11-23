<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Persistence\ConditionFailed;

/**
 * The subscription's inbox already holds the event. The event was delivered again, and it is not applied a second time.
 */
final class AlreadyApplied extends \RuntimeException
{
    public static function by(string $subscription, string $eventId, ConditionFailed $cancelled): self
    {
        return new self(sprintf('The subscription %s applied the event %s already', $subscription, $eventId), previous: $cancelled);
    }
}

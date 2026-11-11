<?php

namespace App\Shared\Messaging;

use App\Shared\Persistence\ConditionFailed;

/**
 * The inbox of the subscription keeps the event already: delivered again, it was applied once, and is not again.
 */
final class AlreadyApplied extends \RuntimeException
{
    public static function by(string $subscription, string $eventId, ConditionFailed $cancelled): self
    {
        return new self(sprintf('The subscription %s applied the event %s already', $subscription, $eventId), previous: $cancelled);
    }
}

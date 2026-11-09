<?php

namespace App\Shared\Messaging;

/**
 * Reactions answering each other without end: the event is not delivered.
 */
final class EventLoop extends \LogicException
{
    public static function reachedBy(TransportedEvent $event, int $maximumDepth): self
    {
        return new self(sprintf('%s of %s reached the depth %d, deeper than %d: reactions answer each other (correlation %s)', $event->detailType, $event->source, $event->depth, $maximumDepth, $event->correlationId));
    }
}

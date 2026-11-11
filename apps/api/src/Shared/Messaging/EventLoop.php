<?php

namespace App\Shared\Messaging;

/**
 * Reactions answering each other without end: an event deeper than MAX_EVENT_DEPTH is not delivered.
 */
final class EventLoop extends \LogicException
{
    public const int MAX_EVENT_DEPTH = 5;

    /**
     * @throws self when the event is deeper than MAX_EVENT_DEPTH
     */
    public static function ensureNotReachedBy(TransportedEvent $event): void
    {
        if ($event->depth > self::MAX_EVENT_DEPTH) {
            throw new self(sprintf('%s of %s reached the depth %d, deeper than %d: reactions answer each other (correlation %s)', $event->detailType, $event->source, $event->depth, self::MAX_EVENT_DEPTH, $event->correlationId));
        }
    }
}

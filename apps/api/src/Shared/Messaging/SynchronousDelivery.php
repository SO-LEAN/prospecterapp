<?php

namespace App\Shared\Messaging;

/**
 * Delivers an event at once, within the command that published it, as it would travel: through JSON, then decoded
 * for each reaction listening to it, the internal ones included; no inbox, a reaction runs once.
 */
final readonly class SynchronousDelivery implements Delivery
{
    public const int MAX_EVENT_DEPTH = 5;

    public function __construct(private Subscriptions $subscriptions, private EventCodec $codec, private Causation $causation)
    {
    }

    public function deliver(TransportedEvent $event): void
    {
        if ($event->depth > self::MAX_EVENT_DEPTH) {
            throw EventLoop::reachedBy($event, self::MAX_EVENT_DEPTH);
        }

        $received = $this->carried($event);

        foreach ($this->subscriptions->reactingTo($received) as $reaction) {
            $this->causation->within($received->causeOfAReaction(), fn () => $reaction($this->codec->decode($received)));
        }
    }

    private function carried(TransportedEvent $event): TransportedEvent
    {
        return TransportedEvent::fromEventBridge(json_decode(json_encode($event->toEventBridge(), \JSON_THROW_ON_ERROR), true, flags: \JSON_THROW_ON_ERROR));
    }
}

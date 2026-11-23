<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

/**
 * Delivers an event at once, within the command that published it. The event goes through JSON as if it travelled.
 * Then it is decoded for each reaction that listens to it, internal ones included. There is no inbox: a reaction
 * runs once.
 */
final readonly class SynchronousDelivery implements Delivery
{
    public function __construct(private Subscriptions $subscriptions, private EventCodec $codec, private Causation $causation)
    {
    }

    public function deliver(TransportedEvent $event): void
    {
        EventLoop::ensureNotReachedBy($event);

        $received = $this->carried($event);

        foreach ($this->subscriptions->reactingTo($received) as $reaction) {
            $this->causation->within($received->causeOfAReaction(), fn () => $reaction->__invoke($this->codec->decode($received)));
        }
    }

    private function carried(TransportedEvent $event): TransportedEvent
    {
        return TransportedEvent::fromEventBridge(json_decode(json_encode($event->toEventBridge(), \JSON_THROW_ON_ERROR), true, flags: \JSON_THROW_ON_ERROR));
    }
}

<?php

namespace App\Shared\Messaging;

/**
 * Brings an event to the reactions of the subscriptions listening to it (`config/subscriptions.json`), the way
 * INTEGRATION_EVENTS_DELIVERY names.
 */
interface Delivery
{
    public function deliver(TransportedEvent $event): void;
}

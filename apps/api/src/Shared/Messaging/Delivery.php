<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

/**
 * Delivers an event to the reactions of the subscriptions that listen to it (`config/subscriptions.json`).
 * INTEGRATION_EVENTS_DELIVERY names the way it delivers.
 */
interface Delivery
{
    public function deliver(TransportedEvent $event): void;
}

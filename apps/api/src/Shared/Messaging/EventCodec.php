<?php

namespace App\Shared\Messaging;

use App\Shared\Identities;

/**
 * Encodes an event to travel, and decodes it back, the domain events and the integration events alike: only
 * their lists differ, and their sources tell them apart when they bear the same name.
 */
final readonly class EventCodec
{
    public function __construct(
        private DomainEventTypes $domainEvents,
        private IntegrationEventTypes $integrationEvents,
        private Payloads $payloads,
        private Identities $identities,
    ) {
    }

    public function encode(object $event, Cause $cause): TransportedEvent
    {
        [$source, $detailType] = $this->domainEvents->typeOf($event::class)
            ?? $this->integrationEvents->typeOf($event::class)
            ?? throw new \LogicException(sprintf('%s is listed neither in DomainEventTypes nor in IntegrationEventTypes', $event::class));
        $payload = $this->payloads->of($event);

        return new TransportedEvent($source, $detailType, $this->identities->next(), $payload['occurredAt'], $cause->correlationId, $cause->causationId, $cause->depth, $payload);
    }

    public function decode(TransportedEvent $event): object
    {
        $class = $this->domainEvents->classOf($event->source, $event->detailType)
            ?? $this->integrationEvents->classOf($event->source, $event->detailType)
            ?? throw new \LogicException(sprintf('No event %s from %s is known', $event->detailType, $event->source));

        return $this->payloads->event($class, $event->payload);
    }
}

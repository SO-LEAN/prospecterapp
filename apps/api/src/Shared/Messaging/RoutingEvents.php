<?php

namespace App\Shared\Messaging;

use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * The events the use cases and the reactions publish: a domain event goes to the translators of its context on
 * the event bus, which publish it for the other contexts, then, like an integration event, to the delivery of the
 * subscriptions. A refusal or a failure of a reaction comes back as it was thrown, so that the command that
 * published refuses or fails with it.
 */
final readonly class RoutingEvents implements Events
{
    public function __construct(
        private MessageBusInterface $eventBus,
        private Delivery $delivery,
        private EventCodec $codec,
        private Causation $causation,
    ) {
    }

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->route($event);
        }
    }

    private function route(object $event): void
    {
        $transported = $this->codec->encode($event, $this->causation->current());

        if ($event instanceof DomainEvent) {
            $this->causation->within($transported->causeOfATranslation(), fn () => $this->translate($event));
        }

        $this->delivery->deliver($transported);
    }

    private function translate(DomainEvent $event): void
    {
        try {
            $this->eventBus->dispatch($event);
        } catch (HandlerFailedException $failure) {
            throw $failure->getPrevious() ?? $failure;
        }
    }
}

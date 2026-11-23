<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Routes the events that the use cases and the reactions publish. A domain event first goes to its context's
 * translators on the event bus, which publish it for the other contexts. Then it goes to the subscriptions' delivery,
 * like an integration event. A reaction's refusal or failure comes back as it was thrown, so that the command that
 * published the event refuses or fails with it.
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

<?php

namespace App\Shared;

use Prospecter\Core\SharedKernel\Application\Port\Events;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * The domain events the use cases release, on the synchronous event bus of Messenger (ADR 0042). A refusal of a handler comes back
 * as it was thrown, so that the command that published refuses with it.
 */
final readonly class MessengerEvents implements Events
{
    public function __construct(private MessageBusInterface $eventBus)
    {
    }

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }

    private function dispatch(object $event): void
    {
        try {
            $this->eventBus->dispatch($event);
        } catch (HandlerFailedException $failure) {
            throw $failure->getPrevious() ?? $failure;
        }
    }
}

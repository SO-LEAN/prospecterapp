<?php

namespace App\Messenger;

use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Sends a command of the core to its handler. Handled synchronously: a refusal of the core is thrown back
 * as is, so that the API translates it like any other (404, 409, 422).
 */
final readonly class CommandBus
{
    public function __construct(private MessageBusInterface $commandBus)
    {
    }

    public function dispatch(object $command): void
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $failure) {
            throw $failure->getPrevious() ?? $failure;
        }
    }
}

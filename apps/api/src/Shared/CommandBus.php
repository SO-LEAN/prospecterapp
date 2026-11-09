<?php

namespace App\Shared;

use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Sends a command of the core to its handler. Handled synchronously: a refusal of the core is thrown back
 * as is, so that the API translates it like any other (404, 409, 422).
 */
final readonly class CommandBus
{
    public function __construct(private MessageBusInterface $commandBus, private UnitOfWork $unitOfWork)
    {
    }

    /**
     * One gesture made of several commands: what they write is kept all at once, or nothing when one of them
     * refuses or fails, a refusal detected at the commit translated like any other (RefusalTranslatingUnitOfWork).
     */
    public function dispatchTogether(object ...$commands): void
    {
        $this->unitOfWork->begin();

        try {
            foreach ($commands as $command) {
                $this->dispatch($command);
            }

            $this->unitOfWork->commit();
        } finally {
            $this->unitOfWork->discard();
        }
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

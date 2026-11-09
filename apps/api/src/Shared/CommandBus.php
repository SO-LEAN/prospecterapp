<?php

namespace App\Shared;

use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;
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
     * refuses or fails.
     */
    public function dispatchTogether(object ...$commands): void
    {
        $this->unitOfWork->begin();

        try {
            foreach ($commands as $command) {
                $this->dispatch($command);
            }

            $this->commitTheUnit();
        } finally {
            $this->unitOfWork->discard();
        }
    }

    /**
     * A refusal detected when the unit is written refuses the gesture like any other.
     */
    private function commitTheUnit(): void
    {
        try {
            $this->unitOfWork->commit();
        } catch (BusinessException $refusal) {
            throw UseCaseRefused::because($refusal);
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

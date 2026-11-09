<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;

/**
 * Decorates a command handler so that what it writes, and what its reactions write, is kept all at once or not
 * at all: the unit of work is committed when the handler succeeds, discarded otherwise.
 */
final readonly class TransactionalCommandHandler implements CommandHandler
{
    public function __construct(private CommandHandler $handler, private UnitOfWork $unitOfWork)
    {
    }

    public function __invoke(object $command): void
    {
        $this->unitOfWork->begin();

        try {
            $this->handler->__invoke($command);
            $this->unitOfWork->commit();
        } finally {
            $this->unitOfWork->discard();
        }
    }
}

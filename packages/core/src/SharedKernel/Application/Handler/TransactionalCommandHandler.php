<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Handler;

use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;

/**
 * Decorates a command handler so that what it writes and what its reactions write are kept all at once or not at
 * all. The decorator commits the unit of work when the handler succeeds and discards it otherwise.
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

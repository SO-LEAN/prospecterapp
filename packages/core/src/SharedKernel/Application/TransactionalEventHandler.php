<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;

/**
 * Decorates an event handler so that what the reaction writes is kept all at once or not at all: within the
 * command that published, it joins its unit of work, and fails it when it fails.
 */
final readonly class TransactionalEventHandler implements EventHandler
{
    public function __construct(private EventHandler $handler, private UnitOfWork $unitOfWork)
    {
    }

    public function __invoke(object $event): void
    {
        $this->unitOfWork->begin();

        try {
            $this->handler->__invoke($event);
            $this->unitOfWork->commit();
        } finally {
            $this->unitOfWork->discard();
        }
    }
}

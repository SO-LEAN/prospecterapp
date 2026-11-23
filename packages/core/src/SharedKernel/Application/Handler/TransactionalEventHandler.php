<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Handler;

use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;

/**
 * Decorates an event handler so that what the reaction writes is kept all at once or not at all. The reaction
 * joins the unit of work of the command that published the event. When the reaction fails, the command fails too.
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

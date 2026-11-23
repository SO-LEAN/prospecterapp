<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;

/**
 * A reaction that is still an event handler of the core, called through its decorators, until it becomes a policy and
 * a command (ADR 0203).
 */
final readonly class HandlerReaction implements Reaction
{
    public function __construct(private EventHandler $handler)
    {
    }

    public function __invoke(object $event): void
    {
        $this->handler->__invoke($event);
    }
}

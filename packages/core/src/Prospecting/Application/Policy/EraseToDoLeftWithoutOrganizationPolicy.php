<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\ToDo\EraseToDoLeftWithoutOrganization\EraseToDoLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\ToDoLeftWithoutOrganization;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a to-do is kept for no organization any more, it is erased.
 */
final readonly class EraseToDoLeftWithoutOrganizationPolicy implements Policy
{
    public function __invoke(ToDoLeftWithoutOrganization $event): EraseToDoLeftWithoutOrganization
    {
        return new EraseToDoLeftWithoutOrganization(
            memberId: $event->by->value,
            toDoId: $event->toDo->value,
            executedAt: $event->occurredAt,
        );
    }
}

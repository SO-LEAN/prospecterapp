<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\ToDo\StopKeepingToDos\StopKeepingToDos;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When the account stops prospecting an organization, the organization stops keeping its to-dos.
 */
final readonly class StopKeepingToDosPolicy implements Policy
{
    public function __invoke(ProspectingStopped $event): StopKeepingToDos
    {
        return new StopKeepingToDos(
            memberId: $event->by->value,
            organizationId: $event->organization->value,
            executedAt: $event->occurredAt,
        );
    }
}

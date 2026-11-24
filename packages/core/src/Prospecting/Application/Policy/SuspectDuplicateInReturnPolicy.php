<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\Duplicate\SuspectDuplicateInReturn\SuspectDuplicateInReturn;
use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspected;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an organization resembles another one, the other one suspects it in return.
 */
final readonly class SuspectDuplicateInReturnPolicy implements Policy
{
    public function __invoke(DuplicateSuspected $event): SuspectDuplicateInReturn
    {
        return new SuspectDuplicateInReturn(
            memberId: $event->by->value,
            organizationId: $event->duplicate->value,
            duplicateId: $event->organization->value,
            executedAt: $event->occurredAt,
        );
    }
}

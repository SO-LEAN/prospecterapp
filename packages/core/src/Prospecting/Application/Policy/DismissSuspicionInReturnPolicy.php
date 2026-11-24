<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\Duplicate\DismissSuspicionInReturn\DismissSuspicionInReturn;
use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspicionDismissed;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a member checks that an organization is no duplicate of another one, the other one knows it in return.
 */
final readonly class DismissSuspicionInReturnPolicy implements Policy
{
    public function __invoke(DuplicateSuspicionDismissed $event): DismissSuspicionInReturn
    {
        return new DismissSuspicionInReturn(
            memberId: $event->by->value,
            organizationId: $event->duplicate->value,
            duplicateId: $event->organization->value,
            executedAt: $event->occurredAt,
        );
    }
}

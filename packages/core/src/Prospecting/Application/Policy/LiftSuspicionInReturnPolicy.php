<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\Duplicate\LiftSuspicionInReturn\LiftSuspicionInReturn;
use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspicionLifted;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an organization no longer resembles another one, or is no longer prospected, the other one lifts its
 * suspicion in return.
 */
final readonly class LiftSuspicionInReturnPolicy implements Policy
{
    public function __invoke(DuplicateSuspicionLifted $event): LiftSuspicionInReturn
    {
        return new LiftSuspicionInReturn(
            memberId: $event->by->value,
            organizationId: $event->duplicate->value,
            duplicateId: $event->organization->value,
            executedAt: $event->occurredAt,
        );
    }
}

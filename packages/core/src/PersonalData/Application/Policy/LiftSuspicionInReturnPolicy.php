<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Policy;

use Prospecter\Core\PersonalData\Application\Duplicate\LiftSuspicionInReturn\LiftSuspicionInReturn;
use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspicionLifted;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a contact no longer resembles another one, or is forgotten, the other contact lifts its suspicion in return.
 */
final readonly class LiftSuspicionInReturnPolicy implements Policy
{
    public function __invoke(DuplicateSuspicionLifted $event): LiftSuspicionInReturn
    {
        return new LiftSuspicionInReturn(
            memberId: $event->by->value,
            contactId: $event->duplicate->value,
            duplicateId: $event->person->value,
            executedAt: $event->occurredAt,
        );
    }
}

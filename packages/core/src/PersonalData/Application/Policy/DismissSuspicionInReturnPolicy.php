<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Policy;

use Prospecter\Core\PersonalData\Application\Duplicate\DismissSuspicionInReturn\DismissSuspicionInReturn;
use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspicionDismissed;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a member checks that a contact is no duplicate of another one, the other contact knows it in return.
 */
final readonly class DismissSuspicionInReturnPolicy implements Policy
{
    public function __invoke(DuplicateSuspicionDismissed $event): DismissSuspicionInReturn
    {
        return new DismissSuspicionInReturn(
            memberId: $event->by->value,
            contactId: $event->duplicate->value,
            duplicateId: $event->person->value,
            executedAt: $event->occurredAt,
        );
    }
}

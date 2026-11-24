<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Policy;

use Prospecter\Core\PersonalData\Application\SuspectDuplicateInReturn\SuspectDuplicateInReturn;
use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspected;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a person resembles a contact, that contact suspects the person in return.
 */
final readonly class SuspectDuplicateInReturnPolicy implements Policy
{
    public function __invoke(DuplicateSuspected $event): SuspectDuplicateInReturn
    {
        return new SuspectDuplicateInReturn(
            memberId: $event->by->value,
            contactId: $event->duplicate->value,
            duplicateId: $event->person->value,
            executedAt: $event->occurredAt,
        );
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Application\EraseToDosOfForgottenContact\EraseToDosOfForgottenContact;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When PersonalData forgets a contact, the contact's to-dos are erased.
 */
final readonly class EraseToDosOfForgottenContactPolicy implements Policy
{
    public function __invoke(ContactForgotten $event): EraseToDosOfForgottenContact
    {
        return new EraseToDosOfForgottenContact(
            accountId: $event->accountId,
            memberId: $event->memberId,
            contactId: $event->personId,
            executedAt: $event->occurredAt,
        );
    }
}

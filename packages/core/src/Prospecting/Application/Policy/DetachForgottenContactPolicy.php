<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Application\DetachForgottenContact\DetachForgottenContact;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When PersonalData forgets a contact, the contact leaves the organizations that name it.
 */
final readonly class DetachForgottenContactPolicy implements Policy
{
    public function __invoke(ContactForgotten $event): DetachForgottenContact
    {
        return new DetachForgottenContact(
            accountId: $event->accountId,
            memberId: $event->memberId,
            contactId: $event->personId,
            executedAt: $event->occurredAt,
        );
    }
}

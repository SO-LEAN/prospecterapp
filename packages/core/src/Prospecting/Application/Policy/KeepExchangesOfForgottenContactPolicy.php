<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Application\Exchange\KeepExchangesOfForgottenContact\KeepExchangesOfForgottenContact;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When PersonalData forgets a contact, the contact's exchanges are kept, to be redacted (ADR 0079).
 */
final readonly class KeepExchangesOfForgottenContactPolicy implements Policy
{
    public function __invoke(ContactForgotten $event): KeepExchangesOfForgottenContact
    {
        return new KeepExchangesOfForgottenContact(
            accountId: $event->accountId,
            memberId: $event->memberId,
            contactId: $event->personId,
            executedAt: $event->occurredAt,
        );
    }
}

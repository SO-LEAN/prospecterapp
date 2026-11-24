<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\Exchange\KeepExchangesOfAttachedContact\KeepExchangesOfAttachedContact;
use Prospecter\Core\Prospecting\Domain\Event\ContactAdded;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a member adds a contact to an organization, the exchanges held while the contact was to attach follow them
 * into it (ADR 0146).
 */
final readonly class KeepExchangesOfAttachedContactPolicy implements Policy
{
    public function __invoke(ContactAdded $event): KeepExchangesOfAttachedContact
    {
        return new KeepExchangesOfAttachedContact(
            accountId: $event->accountId->value,
            memberId: $event->by->value,
            organizationId: $event->organization->value,
            contactId: $event->contact->value,
            executedAt: $event->occurredAt,
        );
    }
}

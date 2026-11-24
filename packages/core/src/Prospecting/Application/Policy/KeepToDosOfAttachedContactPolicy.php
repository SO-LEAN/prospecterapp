<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\ToDo\KeepToDosOfAttachedContact\KeepToDosOfAttachedContact;
use Prospecter\Core\Prospecting\Domain\Event\ContactAdded;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a member adds a contact to an organization, the to-dos held while the contact was to attach follow them
 * into it (ADR 0146).
 */
final readonly class KeepToDosOfAttachedContactPolicy implements Policy
{
    public function __invoke(ContactAdded $event): KeepToDosOfAttachedContact
    {
        return new KeepToDosOfAttachedContact(
            accountId: $event->accountId->value,
            memberId: $event->by->value,
            organizationId: $event->organization->value,
            contactId: $event->contact->value,
            executedAt: $event->occurredAt,
        );
    }
}

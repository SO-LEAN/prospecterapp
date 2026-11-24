<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\AttachContactToAttach\AttachContactToAttach;
use Prospecter\Core\Prospecting\Domain\Event\ContactAdded;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a member adds a contact to an organization, a contact to attach is attached (ADR 0146).
 */
final readonly class AttachContactToAttachPolicy implements Policy
{
    public function __invoke(ContactAdded $event): AttachContactToAttach
    {
        return new AttachContactToAttach(
            accountId: $event->accountId->value,
            memberId: $event->by->value,
            organizationId: $event->organization->value,
            contactId: $event->contact->value,
            executedAt: $event->occurredAt,
        );
    }
}

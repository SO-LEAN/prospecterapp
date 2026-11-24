<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\PersonalData\Contract\Event\ContactCollected;
use Prospecter\Core\Prospecting\Application\Contact\AttachCollectedContact\AttachCollectedContact;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When PersonalData collects a contact, the contact is attached to the organizations it was collected for.
 */
final readonly class AttachCollectedContactPolicy implements Policy
{
    public function __invoke(ContactCollected $event): AttachCollectedContact
    {
        return new AttachCollectedContact(
            memberId: $event->memberId,
            contactId: $event->personId,
            organizationIds: $event->organizationIds,
            executedAt: $event->occurredAt,
        );
    }
}

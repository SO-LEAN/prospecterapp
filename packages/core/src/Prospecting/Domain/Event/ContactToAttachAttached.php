<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact to attach became the contact of an organization: no longer to attach.
 */
final readonly class ContactToAttachAttached extends ContactToAttachEvent
{
    public function __construct(AccountId $accountId, MemberId $by, PersonId $contact, public OrganizationId $organization, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $contact, $occurredAt);
    }
}

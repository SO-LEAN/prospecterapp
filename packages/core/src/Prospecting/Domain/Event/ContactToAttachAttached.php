<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact to attach became an organization's contact. It is no longer to attach.
 */
final readonly class ContactToAttachAttached extends ContactToAttachEvent
{
    public function __construct(AccountId $accountId, MemberId $by, PersonId $contact, public OrganizationId $organization, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $contact, $occurredAt);
    }
}

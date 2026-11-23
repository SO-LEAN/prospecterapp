<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A contact to attach became an organization's contact. It is no longer to attach.
 */
final readonly class ContactToAttachAttached extends ContactToAttachEvent
{
    public function __construct(AccountId $accountId, MemberId $by, ContactId $contact, public OrganizationId $organization, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $contact, $occurredAt);
    }
}

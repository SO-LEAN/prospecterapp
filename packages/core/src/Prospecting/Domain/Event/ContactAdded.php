<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A person the account prospects became a contact of the organization.
 */
final readonly class ContactAdded extends OrganizationEvent
{
    public function __construct(AccountId $accountId, MemberId $by, OrganizationId $organization, public PersonId $contact, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $organization, $occurredAt);
    }
}

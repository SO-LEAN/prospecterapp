<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A member put tags on a contact, or took one off.
 */
final readonly class ContactTagsChanged extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public PersonId $contact, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }

    public function subject(): PersonId
    {
        return $this->contact;
    }
}

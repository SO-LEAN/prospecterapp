<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Something happened to a contact who waits to be attached.
 */
abstract readonly class ContactToAttachEvent extends DomainEvent
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

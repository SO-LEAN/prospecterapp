<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The contact who booked an appointment with a host through the host's public link (ADR 0164). The contact was already
 * known, or was collected to be attached. Their personal data did not change.
 */
final readonly class BookerIdentified extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public PersonId $person, public string $appointmentId, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }

    public function subject(): PersonId
    {
        return $this->person;
    }
}

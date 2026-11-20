<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The contact who booked an appointment with a host by their public link (ADR 0164): known already, or collected to
 * be attached. Their personal data are not what changed.
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

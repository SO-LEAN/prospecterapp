<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A booker booked one of the host's slots through the public link. No member acted. The appointment belongs to the
 * host.
 */
final readonly class SlotBooked extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public AppointmentId $appointmentId, public PersonId $bookerId, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }

    public function subject(): AppointmentId
    {
        return $this->appointmentId;
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A booker booked a slot of the host, by their public link: no member acted, the appointment is the host's.
 */
final readonly class SlotBooked extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $host, public AppointmentId $appointmentId, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $host, $occurredAt);
    }

    public function subject(): AppointmentId
    {
        return $this->appointmentId;
    }
}

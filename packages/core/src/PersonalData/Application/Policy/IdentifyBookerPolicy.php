<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Policy;

use Prospecter\Core\Booking\Contract\Event\SlotBooked;
use Prospecter\Core\PersonalData\Application\Identification\IdentifyBooker\IdentifyBooker;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a slot of a host's booking page is booked, PersonalData identifies who booked it (ADR 0164, 0199).
 */
final readonly class IdentifyBookerPolicy implements Policy
{
    public function __invoke(SlotBooked $event): IdentifyBooker
    {
        return new IdentifyBooker(
            appointmentId: $event->appointmentId,
            accountId: $event->accountId,
            hostId: $event->hostId,
            bookerId: $event->bookerId,
            executedAt: $event->occurredAt,
        );
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\PublishSlotBooked;

use Prospecter\Core\Booking\Contract\Event as Published;
use Prospecter\Core\Booking\Domain\Event\SlotBooked;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Tells the other contexts, in the published language, that a booker booked a slot (ADR 0046, 0164).
 */
final readonly class PublishSlotBookedHandler implements EventHandler
{
    public function __construct(private Events $events)
    {
    }

    public function __invoke(SlotBooked $event): void
    {
        $this->events->publish(new Published\SlotBooked((string) $event->appointmentId, (string) $event->accountId, (string) $event->by, (string) $event->bookerId, $event->occurredAt));
    }
}

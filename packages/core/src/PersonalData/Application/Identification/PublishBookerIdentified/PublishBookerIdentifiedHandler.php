<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\PublishBookerIdentified;

use Prospecter\Core\PersonalData\Contract\Event as Published;
use Prospecter\Core\PersonalData\Domain\Event\BookerIdentified;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Tells the other contexts, in the published language, who booked an appointment (ADR 0046, 0164).
 */
final readonly class PublishBookerIdentifiedHandler implements EventHandler
{
    public function __construct(private Events $events)
    {
    }

    public function __invoke(BookerIdentified $event): void
    {
        $this->events->publish(new Published\BookerIdentified($event->appointmentId, (string) $event->accountId, (string) $event->by, (string) $event->person, $event->occurredAt));
    }
}

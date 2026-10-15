<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\PublishContactCollected;

use Prospecter\Core\PersonalData\Contract\Event as Published;
use Prospecter\Core\PersonalData\Domain\Event\ContactCollected;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Tells the other contexts, in the published language, that a contact was collected (ADR 0046).
 */
final readonly class PublishContactCollectedHandler implements EventHandler
{
    public function __construct(private Events $events)
    {
    }

    public function __invoke(ContactCollected $event): void
    {
        $this->events->publish(new Published\ContactCollected(
            (string) $event->accountId,
            (string) $event->by,
            (string) $event->person,
            array_map(static fn (OrganizationId $organization): string => (string) $organization, $event->organizations),
            $event->occurredAt,
        ));
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\PublishContactCollected;

use Prospecter\Core\PersonalData\Application\Published\Event as Published;
use Prospecter\Core\PersonalData\Domain\Event\ContactCollected;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

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
        $this->events->publish(Published\ContactCollected::of($event));
    }
}

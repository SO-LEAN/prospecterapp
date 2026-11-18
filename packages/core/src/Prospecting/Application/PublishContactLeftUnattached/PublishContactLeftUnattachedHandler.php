<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PublishContactLeftUnattached;

use Prospecter\Core\Prospecting\Contract\Event as Published;
use Prospecter\Core\Prospecting\Domain\Event\ContactLeftUnattached;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Tells the other contexts, in the published language, that a contact was left unattached (ADR 0046, 0146).
 */
final readonly class PublishContactLeftUnattachedHandler implements EventHandler
{
    public function __construct(private Events $events)
    {
    }

    public function __invoke(ContactLeftUnattached $event): void
    {
        $this->events->publish(new Published\ContactLeftUnattached((string) $event->accountId, (string) $event->by, (string) $event->contact, $event->occurredAt));
    }
}

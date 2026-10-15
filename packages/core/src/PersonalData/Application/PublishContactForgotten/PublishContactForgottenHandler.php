<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\PublishContactForgotten;

use Prospecter\Core\PersonalData\Contract\Event as Published;
use Prospecter\Core\PersonalData\Domain\Event\ContactForgotten;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Tells the other contexts, in the published language, that a contact was erased (ADR 0046).
 */
final readonly class PublishContactForgottenHandler implements EventHandler
{
    public function __construct(private Events $events)
    {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $this->events->publish(new Published\ContactForgotten((string) $event->accountId, (string) $event->by, (string) $event->person, $event->occurredAt));
    }
}

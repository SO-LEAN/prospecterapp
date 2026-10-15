<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PublishContactsLeftWithoutOrganization;

use Prospecter\Core\Prospecting\Application\Published\Event as Published;
use Prospecter\Core\Prospecting\Domain\Event\ContactsLeftWithoutOrganization;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Tells the other contexts, in the published language, that contacts were left in no organization (ADR 0046).
 */
final readonly class PublishContactsLeftWithoutOrganizationHandler implements EventHandler
{
    public function __construct(private Events $events)
    {
    }

    public function __invoke(ContactsLeftWithoutOrganization $event): void
    {
        $this->events->publish(Published\ContactsLeftWithoutOrganization::of($event));
    }
}

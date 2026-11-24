<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Policy;

use Prospecter\Core\Connectors\Application\Calendar\UnnameForgottenContact\UnnameForgottenContact;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When PersonalData forgets a contact, the contact's name leaves the calendar events of their exchanges (ADR 0127).
 */
final readonly class UnnameForgottenContactPolicy implements Policy
{
    public function __invoke(ContactForgotten $event): UnnameForgottenContact
    {
        return new UnnameForgottenContact($event->accountId, $event->personId, $event->occurredAt);
    }
}

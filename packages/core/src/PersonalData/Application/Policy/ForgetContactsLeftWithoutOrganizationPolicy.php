<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Policy;

use Prospecter\Core\PersonalData\Application\ForgetContactsLeftWithoutOrganization\ForgetContactsLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Contract\Event\ContactsLeftWithoutOrganization;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When contacts are left without an organization, the member whose gesture left them forgets them (ADR 0042, 0046).
 */
final readonly class ForgetContactsLeftWithoutOrganizationPolicy implements Policy
{
    public function __invoke(ContactsLeftWithoutOrganization $event): ForgetContactsLeftWithoutOrganization
    {
        return new ForgetContactsLeftWithoutOrganization($event->memberId, $event->contactIds, $event->occurredAt);
    }
}

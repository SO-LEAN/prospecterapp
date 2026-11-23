<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Policy;

use Prospecter\Core\PersonalData\Application\ForgetContactLeftUnattached\ForgetContactLeftUnattached;
use Prospecter\Core\Prospecting\Contract\Event\ContactLeftUnattached;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When nobody attached a contact in time, the member who held them forgets them (ADR 0146).
 */
final readonly class ForgetContactLeftUnattachedPolicy implements Policy
{
    public function __invoke(ContactLeftUnattached $event): ForgetContactLeftUnattached
    {
        return new ForgetContactLeftUnattached($event->memberId, $event->personId, $event->occurredAt);
    }
}

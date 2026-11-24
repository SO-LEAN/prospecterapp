<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\StopKeepingExchanges\StopKeepingExchanges;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When the account stops prospecting an organization, the organization stops keeping its exchanges (ADR 0074).
 */
final readonly class StopKeepingExchangesPolicy implements Policy
{
    public function __invoke(ProspectingStopped $event): StopKeepingExchanges
    {
        return new StopKeepingExchanges(
            memberId: $event->by->value,
            organizationId: $event->organization->value,
            executedAt: $event->occurredAt,
        );
    }
}

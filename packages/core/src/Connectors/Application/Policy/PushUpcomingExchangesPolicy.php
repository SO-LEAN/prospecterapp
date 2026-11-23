<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Policy;

use Prospecter\Core\Connectors\Application\PushUpcomingExchanges\PushUpcomingExchanges;
use Prospecter\Core\Connectors\Domain\Event\CalendarRecreated;
use Prospecter\Core\Connectors\Domain\Event\Connected;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a member connects, or when their calendar is created again, the exchanges they still have planned are pushed
 * into it (ADR 0133).
 */
final readonly class PushUpcomingExchangesPolicy implements Policy
{
    public function __invoke(Connected|CalendarRecreated $event): PushUpcomingExchanges
    {
        return new PushUpcomingExchanges($event->accountId->value, $event->by->value, $event->occurredAt);
    }
}

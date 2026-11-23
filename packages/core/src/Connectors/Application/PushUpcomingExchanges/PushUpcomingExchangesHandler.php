<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\PushUpcomingExchanges;

use Prospecter\Core\Connectors\Application\CalendarPusher;
use Prospecter\Core\Connectors\Domain\Event\CalendarToFill;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Port\HeldExchanges;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * Once the member connected, or once their calendar was created again, the exchanges that they still have planned
 * are pushed into it (ADR 0133). The soonest come first, as many as one unit of work writes.
 */
final readonly class PushUpcomingExchangesHandler implements EventHandler
{
    private const int MOST_EXCHANGES_PUSHED_AT_ONCE = 40;

    public function __construct(
        private HeldExchanges $exchanges,
        private CalendarPusher $pusher,
    ) {
    }

    public function __invoke(CalendarToFill $event): void
    {
        $upcoming = array_filter(
            $this->exchanges->plannedBy($event->accountId, $event->subject(), $event->occurredAt),
            static fn (HeldExchange $exchange): bool => $exchange->belongsInTheCalendar(),
        );

        foreach (array_slice($upcoming, 0, self::MOST_EXCHANGES_PUSHED_AT_ONCE) as $exchange) {
            $this->pusher->push($exchange, $event->occurredAt);
        }
    }
}

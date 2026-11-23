<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\PushUpcomingExchanges;

use Prospecter\Core\Connectors\Application\CalendarPusher;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Port\HeldExchanges;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Once the member connected, or once their calendar was created again, the exchanges that they still have planned
 * are pushed into it (ADR 0133). The soonest come first, as many as one unit of work writes.
 */
final readonly class PushUpcomingExchangesHandler implements CommandHandler
{
    private const int MOST_EXCHANGES_PUSHED_AT_ONCE = 40;

    public function __construct(
        private HeldExchanges $exchanges,
        private CalendarPusher $pusher,
    ) {
    }

    public function __invoke(PushUpcomingExchanges $command): void
    {
        $upcoming = array_filter(
            $this->exchanges->plannedBy(new AccountId($command->accountId), new MemberId($command->memberId), $command->executedAt),
            static fn (HeldExchange $exchange): bool => $exchange->belongsInTheCalendar(),
        );

        foreach (array_slice($upcoming, 0, self::MOST_EXCHANGES_PUSHED_AT_ONCE) as $exchange) {
            $this->pusher->push($exchange, $command->executedAt);
        }
    }
}

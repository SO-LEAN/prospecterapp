<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\PushExchange;

use Prospecter\Core\Connectors\Application\CalendarPusher;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Reads the exchange again as it is now, whatever the order its changes arrive in (ADR 0127). A planned exchange is
 * pushed into its holder's calendar. A cancelled or erased exchange is withdrawn from it. A done or missed exchange
 * stays there as it was. The bus passes only the facts of exchanges that are planned, cancelled or erased.
 */
final readonly class PushExchangeHandler implements CommandHandler
{
    public function __construct(private CalendarPusher $pusher)
    {
    }

    public function __invoke(PushExchange $command): void
    {
        $this->pusher->follow(new ExchangeId($command->exchangeId), new AccountId($command->accountId), $command->executedAt);
    }
}

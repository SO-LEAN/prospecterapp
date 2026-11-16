<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\PushExchange;

use Prospecter\Core\Connectors\Application\CalendarPusher;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reacts to each change of an exchange by reading it again as it is now, whatever the order the changes arrive in
 * (ADR 0127): planned, it is pushed into the calendar of its holder; cancelled or erased, it is withdrawn from it;
 * done or missed, it stays there as it was. The bus gives it only the facts of the exchanges planned, cancelled or
 * erased.
 */
final readonly class PushExchangeHandler implements EventHandler
{
    public function __construct(private CalendarPusher $pusher)
    {
    }

    public function __invoke(ExchangeFactsChanged $fact): void
    {
        $this->pusher->follow(new ExchangeId($fact->exchangeId), new AccountId($fact->accountId), $fact->occurredAt);
    }
}

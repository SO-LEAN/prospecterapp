<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Policy;

use Prospecter\Core\Connectors\Application\Calendar\PushExchange\PushExchange;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an exchange changes, it is pushed into its holder's calendar as it is now (ADR 0127).
 */
final readonly class PushExchangePolicy implements Policy
{
    public function __invoke(ExchangeFactsChanged $event): PushExchange
    {
        return new PushExchange($event->accountId, $event->exchangeId, $event->occurredAt);
    }
}

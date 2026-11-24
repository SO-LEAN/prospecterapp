<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\Exchange\EraseExchangeLeftWithoutOrganization\EraseExchangeLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeLeftWithoutOrganization;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an exchange is kept for no organization any more, it is erased (ADR 0074, 0077).
 */
final readonly class EraseExchangeLeftWithoutOrganizationPolicy implements Policy
{
    public function __invoke(ExchangeLeftWithoutOrganization $event): EraseExchangeLeftWithoutOrganization
    {
        return new EraseExchangeLeftWithoutOrganization(
            memberId: $event->by->value,
            exchangeId: $event->exchange->value,
            executedAt: $event->occurredAt,
        );
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Policy;

use Prospecter\Core\Campaigns\Application\ReopenRecipientOfErasedExchange\ReopenRecipientOfErasedExchange;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an exchange of a campaign is erased, the recipient it processed is to be handled again (ADR 0152, 0155).
 */
final readonly class ReopenRecipientOfErasedExchangePolicy implements Policy
{
    public function __invoke(ExchangeFactsChanged $event): ?ReopenRecipientOfErasedExchange
    {
        if ($event->present || null === $event->campaignId) {
            return null;
        }

        return new ReopenRecipientOfErasedExchange(
            accountId: $event->accountId,
            campaignId: $event->campaignId,
            exchangeId: $event->exchangeId,
            erasedWithItsOrganization: $event->erasedWithItsOrganization,
            executedAt: $event->occurredAt,
        );
    }
}

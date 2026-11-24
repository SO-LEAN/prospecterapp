<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Policy;

use Prospecter\Core\Campaigns\Application\Recipient\ProcessRecipientOfCampaignExchange\ProcessRecipientOfCampaignExchange;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When Prospecting logs a recipient's exchange in a campaign, done or missed, the recipient is processed (ADR 0145).
 */
final readonly class ProcessRecipientOfCampaignExchangePolicy implements Policy
{
    /** The statuses of an exchange that took place. A planned exchange processes nobody yet. */
    private const array TAKEN_PLACE = ['done', 'missed'];

    public function __invoke(ExchangeFactsChanged $event): ?ProcessRecipientOfCampaignExchange
    {
        if (!$event->present || null === $event->campaignId || null === $event->contactId || !in_array($event->status, self::TAKEN_PLACE, true)) {
            return null;
        }

        return new ProcessRecipientOfCampaignExchange(
            accountId: $event->accountId,
            campaignId: $event->campaignId,
            contactId: $event->contactId,
            exchangeId: $event->exchangeId,
            outcome: $event->status,
            executedAt: $event->occurredAt,
        );
    }
}

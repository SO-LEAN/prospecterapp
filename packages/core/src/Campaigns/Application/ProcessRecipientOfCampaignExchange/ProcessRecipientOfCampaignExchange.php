<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ProcessRecipientOfCampaignExchange;

final readonly class ProcessRecipientOfCampaignExchange
{
    /**
     * @param string $outcome `done` or `missed`, as the exchange took place
     */
    public function __construct(
        public string $accountId,
        public string $campaignId,
        public string $contactId,
        public string $exchangeId,
        public string $outcome,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

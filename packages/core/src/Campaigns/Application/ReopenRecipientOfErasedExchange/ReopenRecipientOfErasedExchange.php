<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ReopenRecipientOfErasedExchange;

final readonly class ReopenRecipientOfErasedExchange
{
    /**
     * @param bool $erasedWithItsOrganization the exchange went with its organization, rather than a member erasing it
     */
    public function __construct(
        public string $accountId,
        public string $campaignId,
        public string $exchangeId,
        public bool $erasedWithItsOrganization,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

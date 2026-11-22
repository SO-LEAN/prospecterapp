<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign;

/**
 * Which of these contacts have an exchange of the campaign that the account keeps (ADR 0195): asked by Campaigns for
 * its recipients still to handle, before it processes them from the facts of their exchange.
 */
final readonly class WhichContactsHaveExchangesOfCampaign
{
    /**
     * @param list<string> $contactIds
     */
    public function __construct(
        public string $accountId,
        public string $campaignId,
        public array $contactIds,
    ) {
    }
}

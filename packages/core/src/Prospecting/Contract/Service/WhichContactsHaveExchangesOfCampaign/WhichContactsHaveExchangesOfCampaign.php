<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign;

/**
 * Asks which of these contacts have an exchange in the campaign that the account keeps (ADR 0195). Campaigns asks it
 * for its recipients that still need handling, before it processes them from their exchange's facts.
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

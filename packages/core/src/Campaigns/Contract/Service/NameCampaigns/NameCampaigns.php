<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\NameCampaigns;

/**
 * The names of an account's campaigns (ADR 0089, 0145). The query asks about a set. A single campaign is a set of one.
 */
final readonly class NameCampaigns
{
    /**
     * @param list<string> $campaignIds
     */
    public function __construct(
        public string $accountId,
        public array $campaignIds,
    ) {
    }
}

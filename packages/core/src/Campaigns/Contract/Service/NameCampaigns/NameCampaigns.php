<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\NameCampaigns;

/**
 * The names of campaigns of an account (ADR 0089, 0145): asked about a set, a single campaign being a set of one.
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

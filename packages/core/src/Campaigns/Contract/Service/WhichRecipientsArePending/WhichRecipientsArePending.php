<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending;

/**
 * Which of these contacts a campaign of the account keeps as its recipients, and which of them are still to handle
 * (ADR 0089, 0145): asked about a set, a single contact being a set of one.
 */
final readonly class WhichRecipientsArePending
{
    /**
     * @param list<string> $personIds
     */
    public function __construct(
        public string $accountId,
        public string $campaignId,
        public array $personIds,
    ) {
    }
}

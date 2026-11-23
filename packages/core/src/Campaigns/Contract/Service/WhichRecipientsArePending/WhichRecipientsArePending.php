<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending;

/**
 * Which of these contacts an account's campaign keeps as its recipients, and which of them are still to handle (ADR
 * 0089, 0145). The query asks about a set. A single contact is a set of one.
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

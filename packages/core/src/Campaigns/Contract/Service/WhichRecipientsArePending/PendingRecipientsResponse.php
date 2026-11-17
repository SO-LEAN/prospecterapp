<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending;

/**
 * A campaign as a context noting its exchanges needs it: its channel (`email`, `call`, `sms`, `linkedIn`), whether it
 * is started, being led, and, among the contacts asked, those it keeps as recipients and those still to handle.
 */
final readonly class PendingRecipientsResponse
{
    /**
     * @param list<string> $recipientIds
     * @param list<string> $pendingIds
     */
    public function __construct(
        public string $campaignId,
        public string $channel,
        public bool $started,
        public array $recipientIds,
        public array $pendingIds,
    ) {
    }
}

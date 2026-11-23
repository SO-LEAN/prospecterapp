<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending;

/**
 * A campaign as the context that logs its exchanges needs it. It holds the campaign's channel (`email`, `call`, `sms`,
 * `linkedIn`) and whether it is started and running. Among the requested contacts, it lists those the campaign keeps as
 * recipients and those still to handle.
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

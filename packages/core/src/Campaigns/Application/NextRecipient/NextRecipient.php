<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\NextRecipient;

/**
 * The recipient an AI leading a started campaign handles next, and the message it sends them itself: Prospecter
 * sends nothing (ADR 0144).
 */
final readonly class NextRecipient
{
    public function __construct(
        public string $memberId,
        public string $campaignId,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\NextRecipient;

/**
 * The recipient an AI leading a started campaign handles next, and the message it sends them itself: Prospecter
 * sends nothing (ADR 0144). The day of its daily cap is the member's, on their clock.
 */
final readonly class NextRecipient
{
    /**
     * @param string $timeZone the IANA name of the member's clock
     */
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public string $timeZone,
        public \DateTimeImmutable $at,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\NextRecipient;

/**
 * The recipient that an AI running a started campaign handles next, and the message the AI sends them itself.
 * Prospecter sends nothing (ADR 0144). The daily cap counts the member's day, on their clock.
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

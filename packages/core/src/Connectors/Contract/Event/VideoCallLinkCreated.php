<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0137): the calendar of this member created the link
 * of the video call they hold, which asked one.
 */
final readonly class VideoCallLinkCreated
{
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $exchangeId,
        public string $link,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}

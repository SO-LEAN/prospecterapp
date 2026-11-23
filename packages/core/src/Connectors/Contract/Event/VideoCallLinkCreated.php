<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0137). The member's calendar created the link of a
 * video call that the member holds. The video call had asked for a link.
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

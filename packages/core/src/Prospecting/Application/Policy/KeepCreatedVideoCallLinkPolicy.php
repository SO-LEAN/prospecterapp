<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Connectors\Contract\Event\VideoCallLinkCreated;
use Prospecter\Core\Prospecting\Application\Exchange\KeepCreatedVideoCallLink\KeepCreatedVideoCallLink;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When the holder's calendar creates a video call's link, the video call keeps it (ADR 0137).
 */
final readonly class KeepCreatedVideoCallLinkPolicy implements Policy
{
    public function __invoke(VideoCallLinkCreated $event): KeepCreatedVideoCallLink
    {
        return new KeepCreatedVideoCallLink($event->exchangeId, $event->link, $event->occurredAt);
    }
}
